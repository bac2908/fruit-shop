<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderPlacedNotification;
use App\Services\OrderCancellationService;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderEmailNotificationTest extends TestCase
{
    private $createdOrderIds = [];
    private $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'shop.order_automation.order_placed_email_enabled' => true,
            'shop.order_automation.order_cancelled_email_enabled' => true,
        ]);
        Notification::fake();
    }

    protected function tearDown(): void
    {
        DB::table('order_status_histories')->whereIn('order_id', $this->createdOrderIds)->delete();
        DB::table('orders')->whereIn('id', $this->createdOrderIds)->delete();
        DB::table('users')->whereIn('id', $this->createdUserIds)->delete();

        parent::tearDown();
    }

    public function test_order_placed_email_is_sent_once_and_audited(): void
    {
        $order = $this->order();
        $service = app(OrderNotificationService::class);

        $this->assertTrue($service->notifyOrderPlaced($order, $order->user_id));
        $this->assertFalse($service->notifyOrderPlaced($order, $order->user_id));

        Notification::assertSentOnDemand(OrderPlacedNotification::class, 1);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'placed_email_sent',
        ]);
    }

    public function test_cancelled_order_email_is_sent_and_audited(): void
    {
        $order = $this->order();

        DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            app(OrderCancellationService::class)->cancelImmediately(
                $lockedOrder,
                $order->user_id,
                null,
                'Khách hàng hủy đơn thử nghiệm.',
                false
            );
        });

        Notification::assertSentOnDemand(OrderCancelledNotification::class, 1);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'cancelled_email_sent',
        ]);
    }

    private function order(): Order
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->createdUserIds[] = $user->id;
        $code = 'DH-MAIL-' . strtoupper(substr(md5(uniqid('', true)), 0, 10));

        $order = Order::query()->create([
            'code' => $code,
            'public_token' => hash('sha256', $code . uniqid('', true)),
            'user_id' => $user->id,
            'customer_name' => 'Khách kiểm thử',
            'customer_phone' => '+84912345678',
            'customer_email' => $user->email,
            'shipping_address' => '74 Trần Thái Tông',
            'subtotal' => 200000,
            'shipping_fee' => 25000,
            'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_CONFIRMED,
            'discount_total' => 0,
            'total' => 225000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'shipping_status' => Order::SHIPPING_STATUS_PENDING,
        ]);

        $this->createdOrderIds[] = $order->id;

        return $order;
    }
}
