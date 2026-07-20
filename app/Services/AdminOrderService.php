<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderReturnRequest;
use App\Models\OrderStatusHistory;
use App\Support\LocalDateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminOrderService
{
    public function __construct(
        private OrderCancellationService $cancellations,
        private OrderAutomationService $automation,
        private OrderNotificationService $notifications,
        private CustomerNotificationService $customerNotifications,
        private OrderStateTransitionService $stateTransitions,
        private OrderReturnService $returns,
        private SecurityAuditService $audit
    ) {}

    public function updateStatus(Order $order, array $data, int $actorId, array $auditContext): Order
    {
        return DB::transaction(function () use ($order, $data, $actorId, $auditContext) {
            $order = $this->lockedOrder($order->id, ['items', 'cancellationRequests']);
            $nextStatus = $data['status'];

            if ($order->status === $nextStatus) {
                if ($nextStatus === Order::STATUS_DONE) {
                    $this->automation->autoMarkPaymentCollectedOnCompletion($order, $actorId);
                }

                return $order->fresh();
            }

            $this->stateTransitions->ensureCanTransition($order, $nextStatus);
            $this->ensureOperationalRequirements($order, $nextStatus);

            if ($order->hasPendingCancellationRequest() && in_array($nextStatus, [Order::STATUS_SHIPPING, Order::STATUS_DONE], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Đơn đang có yêu cầu hủy. Hãy duyệt hoặc từ chối yêu cầu trước khi giao hàng.',
                ]);
            }

            if ($nextStatus === Order::STATUS_CANCELLED) {
                $this->cancellations->cancelImmediately(
                    $order,
                    $actorId,
                    null,
                    $data['admin_note'] ?: 'Admin hủy đơn từ màn hình quản trị.',
                    false
                );
            } else {
                $note = $data['admin_note']
                    ?: 'Admin chuyển trạng thái sang '.(Order::statusLabels()[$nextStatus] ?? $nextStatus).'.';

                $this->stateTransitions->transition($order, $nextStatus, $actorId, $note);

                if ($nextStatus === Order::STATUS_DONE) {
                    $this->automation->autoMarkPaymentCollectedOnCompletion($order, $actorId);
                }

                $order->refresh();
                if ($nextStatus === Order::STATUS_CONFIRMED) {
                    $this->notifications->notifyOrderConfirmed($order, $actorId);
                } else {
                    $this->customerNotifications->orderStatusChanged($order, $nextStatus);
                }
            }

            $this->audit->record('admin_order_status_changed', $auditContext, [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'status' => $nextStatus,
            ]);

            return $order->fresh();
        });
    }

    public function updateShipping(Order $order, array $data, int $actorId, array $auditContext): Order
    {
        return DB::transaction(function () use ($order, $data, $actorId, $auditContext) {
            $order = $this->lockedOrder($order->id, ['cancellationRequests']);

            if (in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_DONE], true)) {
                throw ValidationException::withMessages([
                    'shipping_fee' => 'Đơn đã kết thúc nên không thể sửa thông tin vận chuyển.',
                ]);
            }

            $previous = [
                'shipping_fee' => (int) $order->shipping_fee,
                'shipping_provider' => $order->shipping_provider,
                'tracking_code' => $order->tracking_code,
            ];
            $shippingFee = (int) $data['shipping_fee'];
            $total = max(0, (int) $order->subtotal + $shippingFee - (int) $order->discount_total);

            if (in_array($order->payment_status, [
                Order::PAYMENT_STATUS_PAID,
                Order::PAYMENT_STATUS_PARTIALLY_REFUNDED,
                Order::PAYMENT_STATUS_REFUNDED,
            ], true)
                && ($shippingFee !== (int) $order->shipping_fee || $total !== (int) $order->total)) {
                throw ValidationException::withMessages([
                    'shipping_fee' => 'Đơn đã thanh toán nên không thể thay đổi phí giao hàng hoặc tổng tiền.',
                ]);
            }

            $note = $data['shipping_delivery_note']
                ?: 'Admin chốt phí giao hàng '.number_format($shippingFee, 0, ',', '.').'đ.';

            $order->forceFill([
                'shipping_fee' => $shippingFee,
                'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_CONFIRMED,
                'shipping_provider' => $data['shipping_provider'] ?? null,
                'tracking_code' => $data['tracking_code'] ?? null,
                'shipping_delivery_eta' => $data['shipping_delivery_eta'] ?? null,
                'shipping_delivery_note' => $note,
                'total' => $total,
                'admin_note' => $this->appendNote($order->admin_note, $note),
            ])->save();

            $shouldAutoConfirm = $order->status === Order::STATUS_PENDING
                && ! $order->hasPendingCancellationRequest()
                && ($order->payment_method === Order::PAYMENT_METHOD_COD
                    || $order->payment_status === Order::PAYMENT_STATUS_PAID);

            if ($shouldAutoConfirm) {
                $this->stateTransitions->transition(
                    $order,
                    Order::STATUS_CONFIRMED,
                    $actorId,
                    'Shop đã chốt phí giao hàng '.number_format($shippingFee, 0, ',', '.').'đ.'
                );
                $this->notifications->notifyOrderConfirmed($order->refresh(), $actorId);
            } else {
                $this->history($order, $actorId, 'shipping_updated', $note);
            }

            $this->audit->record('admin_order_shipping_updated', $auditContext, [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'from' => $previous,
                'to' => [
                    'shipping_fee' => $shippingFee,
                    'shipping_provider' => $data['shipping_provider'] ?? null,
                    'tracking_code' => $data['tracking_code'] ?? null,
                ],
            ]);

            return $order->fresh();
        });
    }

    public function verifyBankPayment(Order $order, array $data, int $actorId, array $auditContext): Order
    {
        return DB::transaction(function () use ($order, $data, $actorId, $auditContext) {
            $order = $this->lockedOrder($order->id, ['cancellationRequests']);

            if ($order->payment_method !== Order::PAYMENT_METHOD_BANK_TRANSFER) {
                throw ValidationException::withMessages([
                    'payment_reference' => 'Chỉ đơn chuyển khoản ngân hàng mới được xác minh thủ công.',
                ]);
            }

            if (in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_DONE], true)) {
                throw ValidationException::withMessages([
                    'payment_reference' => 'Không thể xác minh thanh toán cho đơn đã kết thúc.',
                ]);
            }

            if ($order->payment_status !== Order::PAYMENT_STATUS_UNPAID) {
                throw ValidationException::withMessages([
                    'payment_reference' => 'Thanh toán của đơn này đã được xử lý trước đó.',
                ]);
            }

            $order->forceFill([
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'payment_reference' => $data['payment_reference'],
                'payment_verified_by' => $actorId,
                'payment_verified_at' => now(),
                'paid_at' => now(),
                'admin_note' => $this->appendNote(
                    $order->admin_note,
                    $data['admin_note'] ?: 'Đã đối soát chuyển khoản '.$data['payment_reference'].'.'
                ),
            ])->save();

            $this->history(
                $order,
                $actorId,
                'payment_verified',
                'Đã xác minh chuyển khoản. Mã tham chiếu: '.$data['payment_reference'].'.'
            );
            $this->customerNotifications->paymentReceived($order);

            if ($order->status === Order::STATUS_PENDING
                && ! $order->requiresShippingConfirmation()
                && ! $order->hasPendingCancellationRequest()) {
                $this->stateTransitions->transition(
                    $order,
                    Order::STATUS_CONFIRMED,
                    $actorId,
                    'Đã nhận chuyển khoản và xác nhận đơn.'
                );
                $this->notifications->notifyOrderConfirmed($order->refresh(), $actorId);
            }

            $this->audit->record('admin_order_payment_verified', $auditContext, [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'payment_reference' => $data['payment_reference'],
                'amount' => (int) $order->total,
            ]);

            return $order->fresh();
        });
    }

    public function refundCancelledPayment(Order $order, array $data, int $actorId, array $auditContext): Order
    {
        return DB::transaction(function () use ($order, $data, $actorId, $auditContext) {
            $order = $this->lockedOrder($order->id);

            if ($order->status !== Order::STATUS_CANCELLED || ! in_array($order->payment_status, [
                Order::PAYMENT_STATUS_PAID,
                Order::PAYMENT_STATUS_PARTIALLY_REFUNDED,
            ], true)) {
                throw ValidationException::withMessages([
                    'refund_reference' => 'Chỉ đơn đã hủy và đã thanh toán mới cần xác nhận hoàn tiền.',
                ]);
            }

            $refundedBefore = (int) $order->returnRequests()
                ->where('status', OrderReturnRequest::STATUS_REFUNDED)
                ->sum('refund_amount');
            $refundAmount = max(0, (int) $order->total - $refundedBefore);

            $order->forceFill([
                'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                'refund_reference' => $data['refund_reference'],
                'refunded_by' => $actorId,
                'refunded_at' => now(),
                'admin_note' => $this->appendNote(
                    $order->admin_note,
                    $data['admin_note'] ?: 'Đã hoàn tiền cho đơn bị hủy.'
                ),
            ])->save();

            $this->history(
                $order,
                $actorId,
                'payment_refunded',
                'Đã hoàn '.number_format($refundAmount, 0, ',', '.').'đ. Mã tham chiếu: '.$data['refund_reference'].'.'
            );
            $this->customerNotifications->paymentRefunded($order, $refundAmount, $data['refund_reference']);

            $this->audit->record('admin_order_payment_refunded', $auditContext, [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'refund_reference' => $data['refund_reference'],
                'amount' => $refundAmount,
            ]);

            return $order->fresh();
        });
    }

    public function approveCancellation(OrderCancellationRequest $request, ?string $note, int $actorId, array $auditContext): void
    {
        DB::transaction(function () use ($request, $note, $actorId, $auditContext) {
            $request = $this->lockedCancellation($request->id);
            $this->ensurePendingCancellation($request);
            $order = $this->lockedOrder($request->order_id, ['items']);

            $request->forceFill([
                'status' => OrderCancellationRequest::STATUS_APPROVED,
                'resolved_by' => $actorId,
                'resolved_at' => now(),
                'admin_note' => $note ?: 'Shop đã duyệt yêu cầu hủy đơn.',
            ])->save();

            $this->cancellations->cancelImmediately(
                $order,
                $actorId,
                $request,
                'Shop duyệt yêu cầu hủy. Lý do: '.$request->reason_label.'.',
                false
            );

            $this->audit->record('admin_order_cancellation_approved', $auditContext, [
                'order_id' => $order->id,
                'cancellation_request_id' => $request->id,
            ]);
        });
    }

    public function rejectCancellation(OrderCancellationRequest $request, string $note, int $actorId, array $auditContext): void
    {
        DB::transaction(function () use ($request, $note, $actorId, $auditContext) {
            $request = $this->lockedCancellation($request->id);
            $this->ensurePendingCancellation($request);
            $order = $this->lockedOrder($request->order_id);

            $request->forceFill([
                'status' => OrderCancellationRequest::STATUS_REJECTED,
                'resolved_by' => $actorId,
                'resolved_at' => now(),
                'admin_note' => $note,
            ])->save();

            $order->forceFill([
                'admin_note' => $this->appendNote($order->admin_note, 'Từ chối yêu cầu hủy: '.$note),
            ])->save();
            $this->history($order, $actorId, 'cancel_rejected', 'Shop từ chối yêu cầu hủy: '.$note);

            $this->audit->record('admin_order_cancellation_rejected', $auditContext, [
                'order_id' => $order->id,
                'cancellation_request_id' => $request->id,
            ]);
        });
    }

    public function approveReturn(OrderReturnRequest $request, array $data, int $actorId, array $auditContext): void
    {
        DB::transaction(function () use ($request, $data, $actorId, $auditContext) {
            $request = $this->lockedReturn($request->id);
            $request->setRelation('order', $this->lockedOrder($request->order_id));
            $this->returns->approve($request, $actorId, $data['admin_note'] ?? null, $data['refund_amount'] ?? null);
            $this->auditReturn('approved', $request, $actorId, $auditContext);
        });
    }

    public function rejectReturn(OrderReturnRequest $request, array $data, int $actorId, array $auditContext): void
    {
        DB::transaction(function () use ($request, $data, $actorId, $auditContext) {
            $request = $this->lockedReturn($request->id);
            $request->setRelation('order', $this->lockedOrder($request->order_id));
            $this->returns->reject($request, $actorId, $data['admin_note']);
            $this->auditReturn('rejected', $request, $actorId, $auditContext);
        });
    }

    public function completeReturn(OrderReturnRequest $request, array $data, int $actorId, array $auditContext): void
    {
        DB::transaction(function () use ($request, $data, $actorId, $auditContext) {
            $request = $this->lockedReturn($request->id);
            $request->setRelation('order', $this->lockedOrder($request->order_id));
            $this->returns->completeExchange($request, $actorId, $data['admin_note']);
            $this->auditReturn('completed', $request, $actorId, $auditContext);
        });
    }

    public function refundReturn(OrderReturnRequest $request, array $data, int $actorId, array $auditContext): void
    {
        DB::transaction(function () use ($request, $data, $actorId, $auditContext) {
            $request = $this->lockedReturn($request->id);
            $order = $this->lockedOrder($request->order_id);
            $request->setRelation('order', $order);
            $amount = (int) ($data['refund_amount'] ?? $request->refund_amount ?? $order->total);
            $refundedBefore = (int) $order->returnRequests()
                ->where('status', OrderReturnRequest::STATUS_REFUNDED)
                ->where('id', '!=', $request->id)
                ->sum('refund_amount');
            $remaining = max(0, (int) $order->total - $refundedBefore);

            if (! in_array($order->payment_status, [
                Order::PAYMENT_STATUS_PAID,
                Order::PAYMENT_STATUS_PARTIALLY_REFUNDED,
            ], true)) {
                throw ValidationException::withMessages([
                    'refund_amount' => 'Đơn chưa có khoản thanh toán đủ điều kiện để hoàn tiền.',
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'refund_amount' => 'Chỉ còn có thể hoàn tối đa '.number_format($remaining, 0, ',', '.').'đ cho đơn này.',
                ]);
            }

            $this->returns->markRefunded(
                $request,
                $actorId,
                $amount,
                $data['admin_note'] ?? null,
                $data['refund_reference']
            );
            $this->auditReturn('refunded', $request, $actorId, $auditContext, [
                'amount' => $amount,
                'refund_reference' => $data['refund_reference'],
            ]);
        });
    }

    private function ensureOperationalRequirements(Order $order, string $nextStatus): void
    {
        if (in_array($nextStatus, [Order::STATUS_CONFIRMED, Order::STATUS_SHIPPING], true)
            && $order->requiresShippingConfirmation()) {
            throw ValidationException::withMessages([
                'status' => 'Hãy chốt phí giao hàng trước khi xác nhận đơn.',
            ]);
        }

        if ($nextStatus === Order::STATUS_CONFIRMED
            && in_array($order->payment_method, [Order::PAYMENT_METHOD_BANK_TRANSFER, Order::PAYMENT_METHOD_MOMO], true)
            && $order->payment_status !== Order::PAYMENT_STATUS_PAID) {
            throw ValidationException::withMessages([
                'status' => 'Đơn thanh toán trước chỉ được xác nhận sau khi hệ thống ghi nhận đã thanh toán.',
            ]);
        }

        if ($nextStatus === Order::STATUS_SHIPPING && trim((string) $order->shipping_provider) === '') {
            throw ValidationException::withMessages([
                'status' => 'Hãy cập nhật đơn vị giao hàng trước khi chuyển sang đang giao.',
            ]);
        }
    }

    private function lockedOrder(int $orderId, array $with = []): Order
    {
        return Order::query()->with($with)->whereKey($orderId)->lockForUpdate()->firstOrFail();
    }

    private function lockedCancellation(int $requestId): OrderCancellationRequest
    {
        return OrderCancellationRequest::query()->whereKey($requestId)->lockForUpdate()->firstOrFail();
    }

    private function lockedReturn(int $requestId): OrderReturnRequest
    {
        return OrderReturnRequest::query()->whereKey($requestId)->lockForUpdate()->firstOrFail();
    }

    private function ensurePendingCancellation(OrderCancellationRequest $request): void
    {
        if ($request->status !== OrderCancellationRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'cancellation' => 'Yêu cầu hủy này đã được xử lý trước đó.',
            ]);
        }
    }

    private function history(Order $order, ?int $actorId, string $status, string $note): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $actorId,
            'previous_status' => $order->status,
            'status' => $status,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function auditReturn(
        string $action,
        OrderReturnRequest $request,
        int $actorId,
        array $auditContext,
        array $extra = []
    ): void {
        $this->audit->record('admin_order_return_'.$action, $auditContext, array_merge([
            'order_id' => $request->order_id,
            'return_request_id' => $request->id,
            'actor_id' => $actorId,
        ], $extra));
    }

    private function appendNote(?string $existingNote, string $note): string
    {
        $existingNote = trim((string) $existingNote);
        $newNote = '['.LocalDateTime::format(now()).'] '.$note;

        return $existingNote !== '' ? $existingNote.PHP_EOL.$newNote : $newNote;
    }
}
