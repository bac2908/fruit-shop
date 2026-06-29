<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderCancellationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredBankTransferOrders extends Command
{
    protected $signature = 'shop:cancel-expired-bank-transfers {--hours= : Override expiration hours}';

    protected $description = 'Cancel unpaid bank transfer orders after the configured expiration window.';

    public function handle(OrderCancellationService $cancellationService): int
    {
        $hours = (int) ($this->option('hours') ?: config('shop.order_automation.bank_transfer_expire_hours', 24));
        $hours = max(1, $hours);
        $expiredBefore = Carbon::now()->subHours($hours);

        $orderIds = Order::query()
            ->where('payment_method', Order::PAYMENT_METHOD_BANK_TRANSFER)
            ->where('payment_status', Order::PAYMENT_STATUS_UNPAID)
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
            ->whereIn('shipping_status', [Order::SHIPPING_STATUS_PENDING, Order::SHIPPING_STATUS_PREPARING])
            ->where('created_at', '<=', $expiredBefore)
            ->orderBy('created_at')
            ->pluck('id');

        $cancelled = 0;

        foreach ($orderIds as $orderId) {
            DB::transaction(function () use ($orderId, $hours, $cancellationService, &$cancelled) {
                $order = Order::query()
                    ->with('items')
                    ->whereKey($orderId)
                    ->lockForUpdate()
                    ->first();

                if (!$order || !$this->isStillExpiredBankTransferOrder($order, $hours)) {
                    return;
                }

                $cancellationService->cancelImmediately(
                    $order,
                    null,
                    null,
                    'He thong tu dong huy don chuyen khoan qua ' . $hours . ' gio chua thanh toan.',
                    false
                );

                $cancelled++;
            });
        }

        $this->info('Cancelled expired bank transfer orders: ' . $cancelled);

        return Command::SUCCESS;
    }

    private function isStillExpiredBankTransferOrder(Order $order, int $hours): bool
    {
        return $order->payment_method === Order::PAYMENT_METHOD_BANK_TRANSFER
            && $order->payment_status === Order::PAYMENT_STATUS_UNPAID
            && in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED], true)
            && in_array($order->shipping_status, [Order::SHIPPING_STATUS_PENDING, Order::SHIPPING_STATUS_PREPARING], true)
            && $order->created_at
            && $order->created_at->lte(now()->subHours($hours));
    }
}
