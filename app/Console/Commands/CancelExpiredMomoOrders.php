<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderCancellationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredMomoOrders extends Command
{
    protected $signature = 'shop:cancel-expired-momo-orders {--minutes= : Override expiration minutes}';

    protected $description = 'Cancel unpaid MoMo orders after their payment window expires.';

    public function handle(OrderCancellationService $cancellationService): int
    {
        $minutes = max(1, (int) ($this->option('minutes') ?: config('shop.order_automation.momo_expire_minutes', 30)));

        $orderIds = Order::query()
            ->where('payment_method', Order::PAYMENT_METHOD_MOMO)
            ->where('payment_status', Order::PAYMENT_STATUS_UNPAID)
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
            ->where(function ($query) use ($minutes) {
                $query->where('payment_expires_at', '<=', now())
                    ->orWhere(function ($legacyQuery) use ($minutes) {
                        $legacyQuery->whereNull('payment_expires_at')
                            ->where('created_at', '<=', now()->subMinutes($minutes));
                    });
            })
            ->orderBy('created_at')
            ->pluck('id');

        $cancelled = 0;

        foreach ($orderIds as $orderId) {
            DB::transaction(function () use ($orderId, $minutes, $cancellationService, &$cancelled) {
                $order = Order::query()
                    ->with('items')
                    ->whereKey($orderId)
                    ->lockForUpdate()
                    ->first();

                if (!$order || !$this->isStillExpired($order, $minutes)) {
                    return;
                }

                $cancellationService->cancelImmediately(
                    $order,
                    null,
                    null,
                    'Hệ thống tự động hủy đơn MoMo quá hạn chưa thanh toán.',
                    false
                );

                $cancelled++;
            }, 3);
        }

        $this->info('Cancelled expired MoMo orders: ' . $cancelled);

        return Command::SUCCESS;
    }

    private function isStillExpired(Order $order, int $minutes): bool
    {
        $expiresAt = $order->payment_expires_at ?: optional($order->created_at)->copy()->addMinutes($minutes);

        return $order->payment_method === Order::PAYMENT_METHOD_MOMO
            && $order->payment_status === Order::PAYMENT_STATUS_UNPAID
            && in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED], true)
            && $expiresAt
            && $expiresAt->isPast();
    }
}
