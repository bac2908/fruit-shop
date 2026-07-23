<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\LocalDateTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$fromLocal, $toLocal] = $this->dateRange($request);
        $fromUtc = $fromLocal->copy()->startOfDay()->utc();
        $toUtc = $toLocal->copy()->endOfDay()->utc();
        $days = max(1, $fromLocal->diffInDays($toLocal) + 1);
        $previousFromUtc = $fromUtc->copy()->subDays($days);
        $previousToUtc = $fromUtc->copy()->subSecond();

        $orders = Order::query()->whereBetween('created_at', [$fromUtc, $toUtc]);
        $previousOrders = Order::query()->whereBetween('created_at', [$previousFromUtc, $previousToUtc]);
        $revenueOrders = $this->revenueQuery($fromUtc, $toUtc);
        $previousRevenueOrders = $this->revenueQuery($previousFromUtc, $previousToUtc);

        $orderCount = (clone $orders)->count();
        $revenueOrderCount = (clone $revenueOrders)->count();
        $revenue = (int) (clone $revenueOrders)->sum('total');
        $previousRevenue = (int) (clone $previousRevenueOrders)->sum('total');
        $cancelled = (clone $orders)->where('status', Order::STATUS_CANCELLED)->count();
        $completed = (clone $orders)->where('status', Order::STATUS_DONE)->count();

        $metrics = [
            'revenue' => $revenue,
            'revenue_growth' => $this->growth($revenue, $previousRevenue),
            'orders' => $orderCount,
            'orders_growth' => $this->growth($orderCount, (clone $previousOrders)->count()),
            'aov' => $revenueOrderCount > 0 ? (int) round($revenue / $revenueOrderCount) : 0,
            'completion_rate' => $orderCount > 0 ? round($completed * 100 / $orderCount, 1) : 0,
            'cancellation_rate' => $orderCount > 0 ? round($cancelled * 100 / $orderCount, 1) : 0,
        ];

        $statusRows = collect(Order::statusLabels())
            ->map(function (string $label, string $status) use ($orders, $orderCount) {
                $count = (clone $orders)->where('status', $status)->count();

                return [
                    'key' => $status,
                    'label' => $label,
                    'count' => $count,
                    'percent' => $orderCount > 0 ? round($count * 100 / $orderCount, 1) : 0,
                ];
            })
            ->values();

        $paymentRows = collect(Order::paymentMethodLabels())
            ->map(function (string $label, string $method) use ($orders, $orderCount) {
                $count = (clone $orders)->where('payment_method', $method)->count();

                return [
                    'key' => $method,
                    'label' => $label,
                    'count' => $count,
                    'percent' => $orderCount > 0 ? round($count * 100 / $orderCount, 1) : 0,
                ];
            })
            ->values();

        return view('admin.reports', [
            'metrics' => $metrics,
            'statusRows' => $statusRows,
            'paymentRows' => $paymentRows,
            'topProducts' => $this->topProducts($fromUtc, $toUtc),
            'dailyRevenue' => $this->dailyRevenue($revenueOrders->get(['created_at', 'total']), $fromLocal, $toLocal),
            'hourlyOrders' => $this->hourlyOrders((clone $orders)->get(['created_at'])),
            'from' => $fromLocal->toDateString(),
            'to' => $toLocal->toDateString(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$fromLocal, $toLocal] = $this->dateRange($request);
        $fromUtc = $fromLocal->copy()->startOfDay()->utc();
        $toUtc = $toLocal->copy()->endOfDay()->utc();
        $filename = "bao-cao-don-hang-{$fromLocal->format('Ymd')}-{$toLocal->format('Ymd')}.csv";

        $orders = Order::query()
            ->whereBetween('created_at', [$fromUtc, $toUtc])
            ->orderBy('created_at')
            ->cursor();

        return response()->streamDownload(function () use ($orders) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Mã đơn',
                'Thời gian',
                'Khách hàng',
                'Email',
                'Trạng thái',
                'Thanh toán',
                'Tạm tính',
                'Phí giao hàng',
                'Giảm giá',
                'Tổng cộng',
            ]);

            foreach ($orders as $order) {
                fputcsv($output, [
                    $order->code,
                    LocalDateTime::format($order->created_at),
                    $order->customer_name,
                    $order->customer_email,
                    $order->status_label,
                    $order->payment_method_label.' / '.$order->payment_status_label,
                    (int) $order->subtotal,
                    (int) $order->shipping_fee,
                    (int) $order->discount_total,
                    (int) $order->total,
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function dateRange(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $today = LocalDateTime::now()->startOfDay();
        $from = $request->filled('from')
            ? Carbon::createFromFormat('Y-m-d', (string) $request->query('from'), LocalDateTime::timezone())->startOfDay()
            : $today->copy()->subDays(29);
        $to = $request->filled('to')
            ? Carbon::createFromFormat('Y-m-d', (string) $request->query('to'), LocalDateTime::timezone())->startOfDay()
            : $today;

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

        return [$from, $to];
    }

    private function revenueQuery(Carbon $fromUtc, Carbon $toUtc): Builder
    {
        return Order::query()
            ->whereBetween('created_at', [$fromUtc, $toUtc])
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('payment_status', '!=', Order::PAYMENT_STATUS_REFUNDED)
            ->where(function (Builder $query) {
                $query->whereIn('payment_status', [
                    Order::PAYMENT_STATUS_PAID,
                    Order::PAYMENT_STATUS_PARTIALLY_REFUNDED,
                ])->orWhere('status', Order::STATUS_DONE);
            });
    }

    private function topProducts(Carbon $fromUtc, Carbon $toUtc): Collection
    {
        return OrderItem::query()
            ->selectRaw('product_id, product_name, SUM(qty) as quantity, SUM(line_total) as revenue')
            ->whereHas('order', function (Builder $query) use ($fromUtc, $toUtc) {
                $query->whereBetween('created_at', [$fromUtc, $toUtc])
                    ->where('status', '!=', Order::STATUS_CANCELLED)
                    ->where('payment_status', '!=', Order::PAYMENT_STATUS_REFUNDED);
            })
            ->where('line_total', '>', 0)
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();
    }

    private function dailyRevenue(Collection $orders, Carbon $from, Carbon $to): Collection
    {
        $totals = $orders->groupBy(function (Order $order) {
            return $order->created_at->copy()->setTimezone(LocalDateTime::timezone())->toDateString();
        })->map(fn (Collection $rows) => (int) $rows->sum('total'));

        $days = collect();
        $cursor = $from->copy();

        while ($cursor->lessThanOrEqualTo($to)) {
            $key = $cursor->toDateString();
            $days->push([
                'date' => $key,
                'label' => $cursor->format('d/m'),
                'revenue' => (int) ($totals[$key] ?? 0),
            ]);
            $cursor->addDay();
        }

        return $days;
    }

    private function hourlyOrders(Collection $orders): Collection
    {
        $counts = $orders->countBy(function (Order $order) {
            return $order->created_at->copy()->setTimezone(LocalDateTime::timezone())->hour;
        });
        $max = max(1, (int) $counts->max());

        return collect(range(7, 21))->map(fn (int $hour) => [
            'hour' => sprintf('%02d:00', $hour),
            'count' => (int) ($counts[$hour] ?? 0),
            'level' => (int) ceil(((int) ($counts[$hour] ?? 0)) * 4 / $max),
        ]);
    }

    private function growth(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null;
        }

        return round(($current - $previous) * 100 / $previous, 1);
    }
}
