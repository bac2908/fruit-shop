<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderReturnRequest;
use App\Models\User;
use App\Services\OrderAutomationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_customer_cannot_access_or_mutate_admin_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order(['user_id' => $customer->id, 'customer_email' => $customer->email]);

        $this->actingAs($customer)->get(route('admin.orders'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.orders.show', $order))->assertForbidden();
        $this->actingAs($customer)
            ->patch(route('admin.orders.status', $order), ['status' => Order::STATUS_CONFIRMED])
            ->assertForbidden();
    }

    public function test_admin_can_filter_orders_and_open_a_complete_detail_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order(['customer_name' => 'Khách Admin Order']);

        $this->actingAs($admin)
            ->get(route('admin.orders', ['q' => $order->code, 'status' => Order::STATUS_PENDING]))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Quản lý đơn hàng');

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Tổng thanh toán')
            ->assertSee('Lịch sử xử lý');
    }

    public function test_admin_verifies_bank_transfer_and_paid_order_money_stays_immutable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order([
            'payment_method' => Order::PAYMENT_METHOD_BANK_TRANSFER,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_CONFIRMED,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.payment.verify', $order), [
                'payment_reference' => 'FT-20260720-001',
                'admin_note' => 'Đã kiểm tra sao kê ngân hàng.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(Order::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertSame(Order::STATUS_CONFIRMED, $order->status);
        $this->assertSame('FT-20260720-001', $order->payment_reference);
        $this->assertSame($admin->id, $order->payment_verified_by);
        $this->assertNotNull($order->payment_verified_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'payment_verified',
        ]);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_order_payment_verified',
        ]);

        $originalTotal = $order->total;
        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.shipping', $order), [
                'shipping_fee' => $order->shipping_fee + 10000,
                'shipping_provider' => 'Nội bộ',
            ])
            ->assertSessionHasErrors('shipping_fee');

        $this->assertSame($originalTotal, $order->fresh()->total);
    }

    public function test_shipping_setup_auto_confirms_cod_and_state_machine_completes_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order([
            'shipping_fee' => 20000,
            'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_ESTIMATED,
            'total' => 220000,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.shipping', $order), [
                'shipping_fee' => 30000,
                'shipping_provider' => 'Đội giao hàng nội bộ',
                'tracking_code' => 'SHIP-001',
                'shipping_delivery_eta' => '60 phút',
                'shipping_delivery_note' => 'Gọi khách trước khi giao.',
            ])
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(Order::STATUS_CONFIRMED, $order->status);
        $this->assertSame(230000, $order->total);
        $this->assertSame('SHIP-001', $order->tracking_code);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => Order::STATUS_SHIPPING])
            ->assertSessionHasNoErrors();
        $this->assertSame(Order::SHIPPING_STATUS_SHIPPING, $order->fresh()->shipping_status);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => Order::STATUS_DONE])
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(Order::STATUS_DONE, $order->status);
        $this->assertSame(Order::SHIPPING_STATUS_DELIVERED, $order->shipping_status);
        $this->assertSame(Order::PAYMENT_STATUS_PAID, $order->payment_status);
    }

    public function test_confirmation_rules_match_payment_method_and_customer_timeline(): void
    {
        $automation = app(OrderAutomationService::class);
        $bankOrder = $this->order([
            'payment_method' => Order::PAYMENT_METHOD_BANK_TRANSFER,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
        ]);

        $this->assertFalse($automation->autoConfirmAfterStockReserved($bankOrder));
        $this->assertSame(Order::STATUS_PENDING, $bankOrder->fresh()->status);
        $this->assertTrue($bankOrder->trackingTimeline()->contains(
            fn (array $step) => $step['key'] === Order::STATUS_PENDING && $step['current']
        ));
        $this->assertTrue($bankOrder->trackingTimeline()->contains(
            fn (array $step) => str_contains($step['description'], 'đối soát khoản chuyển tiền')
        ));

        $bankOrder->forceFill([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => now(),
        ])->save();
        $this->assertTrue($automation->autoConfirmAfterStockReserved($bankOrder->refresh()));

        $confirmedTimeline = $bankOrder->fresh()->trackingTimeline();
        $this->assertFalse($confirmedTimeline->contains(
            fn (array $step) => $step['key'] === Order::STATUS_PENDING
        ));
        $this->assertTrue($confirmedTimeline->contains(
            fn (array $step) => $step['key'] === Order::STATUS_CONFIRMED && $step['current']
        ));

        $estimatedCodOrder = $this->order([
            'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_ESTIMATED,
        ]);
        $this->assertFalse($automation->autoConfirmAfterStockReserved($estimatedCodOrder));
        $this->assertSame(Order::STATUS_PENDING, $estimatedCodOrder->fresh()->status);
    }

    public function test_admin_order_screen_explains_why_confirmation_is_waiting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bankOrder = $this->order([
            'payment_method' => Order::PAYMENT_METHOD_BANK_TRANSFER,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $bankOrder))
            ->assertOk()
            ->assertSee('Chưa đối soát được khoản chuyển tiền của khách.')
            ->assertSee('Xác nhận đã nhận tiền')
            ->assertDontSee('<option value="confirmed">', false);

        $codOrder = $this->order();
        $this->actingAs($admin)
            ->get(route('admin.orders.show', $codOrder))
            ->assertOk()
            ->assertSee('Đơn đã đủ điều kiện và đang chờ nhân viên xác nhận.')
            ->assertSee('<option value="confirmed">Đã xác nhận</option>', false);
    }

    public function test_paid_cancellation_waits_for_real_refund_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order([
            'user_id' => $customer->id,
            'customer_email' => $customer->email,
            'status' => Order::STATUS_CONFIRMED,
            'payment_method' => Order::PAYMENT_METHOD_BANK_TRANSFER,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => now(),
        ]);
        $cancellation = OrderCancellationRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'status' => OrderCancellationRequest::STATUS_PENDING,
            'reason' => OrderCancellationRequest::REASON_NO_NEED,
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.cancellations.approve', $cancellation), [
                'admin_note' => 'Đồng ý hủy theo yêu cầu khách.',
            ])
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertSame(OrderCancellationRequest::STATUS_APPROVED, $cancellation->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.payment.refund', $order), [
                'refund_reference' => 'REFUND-CANCEL-001',
                'admin_note' => 'Đã hoàn tiền qua ngân hàng.',
            ])
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(Order::PAYMENT_STATUS_REFUNDED, $order->payment_status);
        $this->assertSame('REFUND-CANCEL-001', $order->refund_reference);
        $this->assertSame($admin->id, $order->refunded_by);
        $this->assertNotNull($order->refunded_at);
    }

    public function test_admin_approves_and_records_a_real_return_refund(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order([
            'user_id' => $customer->id,
            'customer_email' => $customer->email,
            'status' => Order::STATUS_DONE,
            'shipping_status' => Order::SHIPPING_STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => now(),
        ]);
        $returnRequest = OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'status' => OrderReturnRequest::STATUS_PENDING,
            'type' => OrderReturnRequest::TYPE_REFUND,
            'reason' => OrderReturnRequest::REASON_DAMAGED,
            'refund_method' => OrderReturnRequest::REFUND_METHOD_BANK,
            'refund_account' => 'Ngân hàng thử nghiệm 0123456789',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.returns.approve', $returnRequest), [
                'refund_amount' => $order->total,
                'admin_note' => 'Bằng chứng hợp lệ, duyệt hoàn toàn bộ.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderReturnRequest::STATUS_APPROVED, $returnRequest->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.returns.refund', $returnRequest), [
                'refund_amount' => $order->total,
                'refund_reference' => 'REFUND-RETURN-001',
                'admin_note' => 'Đã chuyển khoản hoàn tiền.',
            ])
            ->assertSessionHasNoErrors();

        $returnRequest->refresh();
        $order->refresh();
        $this->assertSame(OrderReturnRequest::STATUS_REFUNDED, $returnRequest->status);
        $this->assertSame(Order::PAYMENT_STATUS_REFUNDED, $order->payment_status);
        $this->assertSame('REFUND-RETURN-001', $order->refund_reference);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_order_return_refunded',
        ]);
    }

    public function test_partial_refund_is_distinct_from_full_refund(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order([
            'status' => Order::STATUS_DONE,
            'shipping_status' => Order::SHIPPING_STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => now(),
        ]);
        $returnRequest = OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'status' => OrderReturnRequest::STATUS_APPROVED,
            'type' => OrderReturnRequest::TYPE_REFUND,
            'reason' => OrderReturnRequest::REASON_MISSING_ITEM,
            'refund_amount' => 50000,
            'requested_at' => now(),
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.refund', $returnRequest), [
                'refund_amount' => 50000,
                'refund_reference' => 'REFUND-PARTIAL-001',
                'admin_note' => 'Hoàn tiền cho sản phẩm bị thiếu.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(Order::PAYMENT_STATUS_PARTIALLY_REFUNDED, $order->fresh()->payment_status);
    }

    public function test_cumulative_refunds_cannot_exceed_the_order_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order([
            'status' => Order::STATUS_DONE,
            'shipping_status' => Order::SHIPPING_STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_STATUS_PARTIALLY_REFUNDED,
            'paid_at' => now(),
        ]);

        OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'status' => OrderReturnRequest::STATUS_REFUNDED,
            'type' => OrderReturnRequest::TYPE_REFUND,
            'reason' => OrderReturnRequest::REASON_MISSING_ITEM,
            'refund_amount' => 150000,
            'requested_at' => now(),
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
            'refunded_at' => now(),
        ]);
        $nextRequest = OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'status' => OrderReturnRequest::STATUS_APPROVED,
            'type' => OrderReturnRequest::TYPE_REFUND,
            'reason' => OrderReturnRequest::REASON_DAMAGED,
            'refund_amount' => 100000,
            'requested_at' => now(),
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.returns.refund', $nextRequest), [
                'refund_amount' => 100000,
                'refund_reference' => 'REFUND-OVER-001',
                'admin_note' => 'Thử hoàn vượt số dư còn lại.',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('refund_amount');

        $this->assertSame(OrderReturnRequest::STATUS_APPROVED, $nextRequest->fresh()->status);
        $this->assertSame(Order::PAYMENT_STATUS_PARTIALLY_REFUNDED, $order->fresh()->payment_status);
    }

    public function test_approved_exchange_can_be_marked_completed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order([
            'status' => Order::STATUS_DONE,
            'shipping_status' => Order::SHIPPING_STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);
        $returnRequest = OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'status' => OrderReturnRequest::STATUS_APPROVED,
            'type' => OrderReturnRequest::TYPE_EXCHANGE,
            'reason' => OrderReturnRequest::REASON_DAMAGED,
            'requested_at' => now(),
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.returns.complete', $returnRequest), [
                'admin_note' => 'Đã giao sản phẩm thay thế và thu hồi hàng lỗi.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderReturnRequest::STATUS_COMPLETED, $returnRequest->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'return_completed',
        ]);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_order_return_completed',
        ]);
    }

    private function order(array $overrides = []): Order
    {
        $code = 'DH-ADMIN-'.Str::upper(Str::random(10));

        return Order::query()->create(array_merge([
            'code' => $code,
            'public_token' => hash('sha256', $code.Str::random(30)),
            'user_id' => null,
            'customer_name' => 'Khách kiểm thử admin',
            'customer_phone' => '+84912345678',
            'customer_email' => 'admin-order-test@example.com',
            'shipping_address' => '74 Trần Thái Tông, Hà Nội',
            'subtotal' => 200000,
            'shipping_fee' => 25000,
            'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_CONFIRMED,
            'discount_total' => 0,
            'total' => 225000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'shipping_status' => Order::SHIPPING_STATUS_PENDING,
            'shipping_delivery_method' => Order::DELIVERY_METHOD_LOCAL_EXPRESS,
            'shipping_delivery_eta' => '30 - 90 phút',
        ], $overrides));
    }
}
