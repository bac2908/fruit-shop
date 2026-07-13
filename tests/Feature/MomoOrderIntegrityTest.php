<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\MomoCallbackService;
use App\Services\OrderStateTransitionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MomoOrderIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.momo.partner_code' => 'MOMO',
            'services.momo.access_key' => 'test-access-key',
            'services.momo.secret_key' => 'test-secret-key',
            'shop.order_automation.order_confirmed_email_enabled' => false,
            'shop.order_automation.order_cancelled_email_enabled' => false,
        ]);
    }

    public function test_valid_callback_locks_order_and_is_idempotent(): void
    {
        $order = $this->momoOrder();
        $payload = $this->signedPayload($order, ['transId' => '900000000001']);
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        $first = app(MomoCallbackService::class)->handle($payload, $order->code);
        $second = app(MomoCallbackService::class)->handle($payload, $order->code);

        $this->assertTrue($first['accepted']);
        $this->assertTrue($first['paid']);
        $this->assertTrue($second['accepted']);
        $this->assertTrue($second['paid']);
        $this->assertTrue(collect($queries)->contains(fn ($sql) => strpos($sql, 'for update') !== false));

        $order->refresh();
        $this->assertSame(Order::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertSame('900000000001', $order->momo_transaction_id);
        $this->assertSame(Order::STATUS_CONFIRMED, $order->status);
    }

    public function test_callback_rejects_mismatched_amount_request_order_and_transaction_ids(): void
    {
        $service = app(MomoCallbackService::class);

        $amountOrder = $this->momoOrder();
        $amountResult = $service->handle($this->signedPayload($amountOrder, [
            'amount' => $amountOrder->total + 1000,
            'transId' => '900000000002',
        ]), $amountOrder->code);
        $this->assertFalse($amountResult['accepted']);
        $this->assertSame(Order::PAYMENT_STATUS_UNPAID, $amountOrder->fresh()->payment_status);

        $requestOrder = $this->momoOrder();
        $requestResult = $service->handle($this->signedPayload($requestOrder, [
            'requestId' => 'WRONG-REQUEST-ID',
            'transId' => '900000000003',
        ]), $requestOrder->code);
        $this->assertFalse($requestResult['accepted']);

        $orderIdOrder = $this->momoOrder();
        $orderIdResult = $service->handle($this->signedPayload($orderIdOrder, [
            'orderId' => 'WRONG-ORDER-ID',
            'transId' => '900000000004',
        ]), $orderIdOrder->code);
        $this->assertFalse($orderIdResult['accepted']);

        $transactionOrder = $this->momoOrder();
        $transactionResult = $service->handle($this->signedPayload($transactionOrder, [
            'transId' => 'not-a-number',
        ]), $transactionOrder->code);
        $this->assertFalse($transactionResult['accepted']);
        $this->assertSame(Order::PAYMENT_STATUS_UNPAID, $transactionOrder->fresh()->payment_status);
    }

    public function test_invalid_order_state_transition_is_rejected(): void
    {
        $order = $this->momoOrder();

        $this->expectException(ValidationException::class);
        app(OrderStateTransitionService::class)->transition(
            $order,
            Order::STATUS_DONE,
            null,
            'Invalid shortcut.'
        );
    }

    public function test_paid_order_money_is_immutable(): void
    {
        $order = $this->momoOrder([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => now(),
            'momo_transaction_id' => '900000000005',
        ]);

        $this->expectException(\LogicException::class);
        $order->forceFill([
            'shipping_fee' => $order->shipping_fee + 1000,
            'total' => $order->total + 1000,
        ])->save();
    }

    public function test_expired_unpaid_momo_order_is_cancelled(): void
    {
        $order = $this->momoOrder([
            'payment_expires_at' => now()->subMinute(),
        ]);

        $this->artisan('shop:cancel-expired-momo-orders')
            ->assertExitCode(0);

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::PAYMENT_STATUS_UNPAID, $order->payment_status);
    }

    private function momoOrder(array $overrides = []): Order
    {
        $user = User::factory()->create(['role' => 'customer']);
        $code = 'DH-TEST-' . strtoupper(substr(md5(uniqid('', true)), 0, 12));

        return Order::query()->create(array_merge([
            'code' => $code,
            'public_token' => hash('sha256', $code . uniqid('', true)),
            'user_id' => $user->id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '+84912345678',
            'customer_email' => $user->email,
            'shipping_address' => 'Test address',
            'subtotal' => 200000,
            'shipping_fee' => 25000,
            'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_CONFIRMED,
            'discount_total' => 0,
            'total' => 225000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => Order::PAYMENT_METHOD_MOMO,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'momo_request_id' => $code,
            'payment_expires_at' => now()->addMinutes(30),
            'shipping_status' => Order::SHIPPING_STATUS_PENDING,
        ], $overrides));
    }

    private function signedPayload(Order $order, array $overrides = []): array
    {
        $payload = array_merge([
            'partnerCode' => 'MOMO',
            'orderId' => $order->code,
            'requestId' => $order->momo_request_id,
            'amount' => (string) $order->total,
            'orderInfo' => 'Thanh toan don hang ' . $order->code,
            'orderType' => 'momo_wallet',
            'transId' => '900000000000',
            'resultCode' => 0,
            'message' => 'Successful.',
            'payType' => 'qr',
            'responseTime' => '1780000000000',
            'extraData' => '',
        ], $overrides);

        $signatureFields = [
            'accessKey' => config('services.momo.access_key'),
            'amount' => $payload['amount'],
            'extraData' => $payload['extraData'],
            'message' => $payload['message'],
            'orderId' => $payload['orderId'],
            'orderInfo' => $payload['orderInfo'],
            'orderType' => $payload['orderType'],
            'partnerCode' => $payload['partnerCode'],
            'payType' => $payload['payType'],
            'requestId' => $payload['requestId'],
            'responseTime' => $payload['responseTime'],
            'resultCode' => $payload['resultCode'],
            'transId' => $payload['transId'],
        ];

        $rawHash = collect($signatureFields)
            ->map(fn ($value, $key) => $key . '=' . $value)
            ->implode('&');
        $payload['signature'] = hash_hmac('sha256', $rawHash, config('services.momo.secret_key'));

        return $payload;
    }
}
