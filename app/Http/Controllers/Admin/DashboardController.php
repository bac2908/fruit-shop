<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\LocalDateTime;

class DashboardController extends Controller
{
    public function index()
    {
        $todayStart = LocalDateTime::now()->startOfDay()->utc();
        $todayEnd = LocalDateTime::now()->endOfDay()->utc();
        $thirtyDaysAgo = LocalDateTime::now()->subDays(30)->startOfDay()->utc();

        $ordersLast30Days = Order::query()->where('created_at', '>=', $thirtyDaysAgo);
        $orders30Count = (clone $ordersLast30Days)->count();
        $activeRevenueQuery = (clone $ordersLast30Days)->where('status', '!=', Order::STATUS_CANCELLED);

        $completedCount = (clone $ordersLast30Days)->where('status', Order::STATUS_DONE)->count();
        $cancelledCount = (clone $ordersLast30Days)->where('status', Order::STATUS_CANCELLED)->count();
        $paidCount = (clone $ordersLast30Days)->where('payment_status', Order::PAYMENT_STATUS_PAID)->count();
        $codCount = (clone $ordersLast30Days)->where('payment_method', Order::PAYMENT_METHOD_COD)->count();

        $metrics = [
            'orders_today' => Order::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'monthly_revenue' => (int) (clone $activeRevenueQuery)->sum('total'),
            'new_customers' => User::query()
                ->where('role', 'customer')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count(),
            'conversion_rate' => $orders30Count > 0 ? round(($completedCount / max(1, $orders30Count)) * 100, 1) : 0,
            'aov' => $orders30Count > 0 ? (int) round((clone $activeRevenueQuery)->avg('total')) : 0,
            'return_rate' => $orders30Count > 0 ? round(($cancelledCount / max(1, $orders30Count)) * 100, 1) : 0,
            'cod_share' => $orders30Count > 0 ? round(($codCount / max(1, $orders30Count)) * 100, 1) : 0,
        ];

        $weeklyRevenue = $this->getWeeklyRevenue();
        $funnel = $this->getOrderFunnel($orders30Count, $paidCount, $completedCount);

        $recentOrders = Order::query()
            ->latest()
            ->take(8)
            ->get();

        $lowStockProducts = Product::query()
            ->with('category')
            ->where(function ($query) {
                $query->whereColumn('stock', '<=', 'low_stock_threshold')
                    ->orWhere('stock', '<=', 5);
            })
            ->orderBy('stock')
            ->take(8)
            ->get();

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'weeklyRevenue' => $weeklyRevenue,
            'funnel' => $funnel,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }

    private function getWeeklyRevenue(): array
    {
        $localStart = LocalDateTime::now()->subDays(6)->startOfDay();
        $start = $localStart->copy()->utc();
        $localOffset = LocalDateTime::now()->format('P');
        $rawRevenue = Order::query()
            ->selectRaw("DATE(CONVERT_TZ(created_at, '+00:00', ?)) as order_date, SUM(total) as revenue", [$localOffset])
            ->where('created_at', '>=', $start)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->groupBy('order_date')
            ->pluck('revenue', 'order_date');

        $maxRevenue = max(1, (int) $rawRevenue->max());
        $values = [];

        for ($i = 0; $i < 7; $i++) {
            $dateKey = $localStart->copy()->addDays($i)->toDateString();
            $revenue = (int) ($rawRevenue[$dateKey] ?? 0);
            $values[] = max(8, (int) round(($revenue / $maxRevenue) * 100));
        }

        return $values;
    }

    private function getOrderFunnel(int $orders30Count, int $paidCount, int $completedCount): array
    {
        $activeCount = Order::query()
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_SHIPPING])
            ->count();

        return [
            ['label' => 'Don tao moi 30 ngay', 'value' => $this->percent($orders30Count, max(1, $orders30Count))],
            ['label' => 'Don dang xu ly', 'value' => $this->percent($activeCount, max(1, $orders30Count))],
            ['label' => 'Thanh toan thanh cong', 'value' => $this->percent($paidCount, max(1, $orders30Count))],
            ['label' => 'Don da giao', 'value' => $this->percent($completedCount, max(1, $orders30Count))],
        ];
    }

    private function percent(int $value, int $base): int
    {
        return (int) max(0, min(100, round(($value / max(1, $base)) * 100)));
    }
}
