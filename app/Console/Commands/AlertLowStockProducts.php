<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockProductsNotification;
use App\Services\SettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlertLowStockProducts extends Command
{
    protected $signature = 'shop:alert-low-stock {--limit=20 : Maximum products included in the alert}';

    protected $description = 'Send a low stock email alert to admin users.';

    public function handle(SettingService $settings): int
    {
        if (! $settings->bool('low_stock_alert_enabled', (bool) config('shop.order_automation.low_stock_alert_enabled', true))) {
            $this->info('Low stock alerts are disabled.');
            return Command::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $fallbackThreshold = max(0, $settings->int('low_stock_default_threshold', (int) config('shop.order_automation.low_stock_fallback_threshold', 5)));

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->where(function ($query) use ($fallbackThreshold) {
                $query->where(function ($thresholdQuery) {
                    $thresholdQuery->where('low_stock_threshold', '>', 0)
                        ->whereColumn('stock', '<=', 'low_stock_threshold');
                })->orWhere(function ($fallbackQuery) use ($fallbackThreshold) {
                    $fallbackQuery->where(function ($emptyThresholdQuery) {
                        $emptyThresholdQuery->whereNull('low_stock_threshold')
                            ->orWhere('low_stock_threshold', '<=', 0);
                    })->where('stock', '<=', $fallbackThreshold);
                });
            })
            ->orderBy('stock')
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            $this->info('No low stock products.');
            return Command::SUCCESS;
        }

        $admins = User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin email found for low stock alert.');
            return Command::SUCCESS;
        }

        $sent = 0;
        foreach ($admins as $admin) {
            try {
                $admin->notify(new LowStockProductsNotification($products));
                $sent++;
            } catch (Throwable $exception) {
                Log::warning('Cannot send low stock alert.', [
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info('Low stock products: ' . $products->count() . '. Alert emails sent: ' . $sent . '.');

        return Command::SUCCESS;
    }
}
