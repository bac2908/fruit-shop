<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\Product;
use App\Models\UserVoucher;

class OrderCancellationService
{
    private $notifications;
    private $stateTransitions;

    public function __construct(
        OrderNotificationService $notifications,
        OrderStateTransitionService $stateTransitions
    )
    {
        $this->notifications = $notifications;
        $this->stateTransitions = $stateTransitions;
    }

    public function cancelImmediately(
        Order $order,
        ?int $actorId,
        ?OrderCancellationRequest $cancelRequest = null,
        ?string $note = null,
        bool $markRefunded = false
    ): void {
        if ($order->status === Order::STATUS_CANCELLED) {
            return;
        }

        $this->stateTransitions->ensureCanTransition($order, Order::STATUS_CANCELLED);

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (!$item->product_id || (int) $item->qty <= 0) {
                continue;
            }

            $product = Product::query()
                ->whereKey($item->product_id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                continue;
            }

            $stockBefore = (int) $product->stock;
            $stockAfter = $stockBefore + (int) $item->qty;

            $product->forceFill(['stock' => $stockAfter])->save();

            InventoryMovement::query()->create([
                'product_id' => $product->id,
                'order_id' => $order->id,
                'user_id' => $actorId,
                'type' => 'cancel',
                'quantity' => (int) $item->qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_cost' => $product->cost_price,
                'note' => 'Hoan kho do huy don ' . $order->code,
            ]);
        }

        $this->restoreCouponAfterCancel($order);

        $cancelNote = $note ?: 'Don hang da duoc huy.';
        $payload = [
            'shipping_status' => Order::SHIPPING_STATUS_FAILED,
            'cancelled_at' => now(),
        ];

        if ($markRefunded && $order->payment_status === Order::PAYMENT_STATUS_PAID) {
            $payload['payment_status'] = Order::PAYMENT_STATUS_REFUNDED;
        }

        $this->stateTransitions->transition(
            $order,
            Order::STATUS_CANCELLED,
            $actorId,
            $cancelNote,
            $payload
        );

        if ($cancelRequest && $cancelRequest->status !== OrderCancellationRequest::STATUS_APPROVED) {
            $cancelRequest->forceFill([
                'status' => OrderCancellationRequest::STATUS_APPROVED,
                'resolved_by' => $actorId,
                'resolved_at' => now(),
            ])->save();
        }

        $order->refresh();
        $this->notifications->notifyOrderCancelled($order, $actorId, $cancelNote);
    }

    private function restoreCouponAfterCancel(Order $order): void
    {
        $usage = CouponUsage::query()
            ->where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if (!$usage) {
            return;
        }

        $coupon = Coupon::query()
            ->whereKey($usage->coupon_id)
            ->lockForUpdate()
            ->first();

        if ($coupon && (int) $coupon->used_count > 0) {
            $coupon->decrement('used_count');
        }

        if ($usage->coupon_id && $usage->used_at) {
            $userVoucher = UserVoucher::query()
                ->where('user_id', $order->user_id)
                ->where('coupon_id', $usage->coupon_id)
                ->whereNotNull('used_at')
                ->whereBetween('used_at', [
                    $usage->used_at->copy()->subSeconds(10),
                    $usage->used_at->copy()->addSeconds(10),
                ])
                ->orderByDesc('used_at')
                ->first();

            if ($userVoucher) {
                $userVoucher->forceFill(['used_at' => null])->save();
            }
        }

        $usage->delete();
    }

}
