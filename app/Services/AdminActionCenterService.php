<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderReturnRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdminActionCenterService
{
    public function __construct(private SettingService $settings)
    {
    }

    public function summary(int $limit = 8, ?User $user = null): array
    {
        $counts = [
            'pending_orders' => $this->allowed($user, 'orders.view') && $this->tableExists('orders')
                ? Order::query()->where('status', Order::STATUS_PENDING)->count()
                : 0,
            'low_stock' => $this->allowed($user, 'catalog.manage') && $this->tableExists('products')
                ? $this->lowStockQuery()->count()
                : 0,
            'new_contacts' => $this->allowed($user, 'support.manage') && $this->tableExists('contact_messages')
                ? ContactMessage::query()->where('status', ContactMessage::STATUS_NEW)->count()
                : 0,
            'cancellations' => $this->allowed($user, 'orders.manage') && $this->tableExists('order_cancellation_requests')
                ? OrderCancellationRequest::query()->where('status', OrderCancellationRequest::STATUS_PENDING)->count()
                : 0,
            'returns' => $this->allowed($user, 'orders.manage') && $this->tableExists('order_return_requests')
                ? OrderReturnRequest::query()->where('status', OrderReturnRequest::STATUS_PENDING)->count()
                : 0,
        ];

        return [
            'counts' => $counts,
            'total' => array_sum($counts),
            'items' => $this->items($limit, $user),
        ];
    }

    public function groups(int $limit = 20, ?User $user = null): array
    {
        return [
            'pendingOrders' => $this->allowed($user, 'orders.view') && $this->tableExists('orders')
                ? Order::query()->where('status', Order::STATUS_PENDING)->latest()->limit($limit)->get()
                : collect(),
            'lowStockProducts' => $this->allowed($user, 'catalog.manage') && $this->tableExists('products')
                ? $this->lowStockQuery()->orderBy('stock')->limit($limit)->get()
                : collect(),
            'newContacts' => $this->allowed($user, 'support.manage') && $this->tableExists('contact_messages')
                ? ContactMessage::query()->where('status', ContactMessage::STATUS_NEW)->latest()->limit($limit)->get()
                : collect(),
            'cancellationRequests' => $this->allowed($user, 'orders.manage') && $this->tableExists('order_cancellation_requests')
                ? OrderCancellationRequest::query()
                    ->with('order:id,code,total,status')
                    ->where('status', OrderCancellationRequest::STATUS_PENDING)
                    ->latest('requested_at')
                    ->limit($limit)
                    ->get()
                : collect(),
            'returnRequests' => $this->allowed($user, 'orders.manage') && $this->tableExists('order_return_requests')
                ? OrderReturnRequest::query()
                    ->with('order:id,code,total,status')
                    ->where('status', OrderReturnRequest::STATUS_PENDING)
                    ->latest('requested_at')
                    ->limit($limit)
                    ->get()
                : collect(),
        ];
    }

    private function items(int $limit, ?User $user): Collection
    {
        $groups = $this->groups(max(3, $limit), $user);
        $items = collect();

        foreach ($groups['pendingOrders'] as $order) {
            $items->push([
                'type' => 'order',
                'icon' => 'ri-shopping-bag-3-line',
                'title' => 'Đơn '.$order->code.' đang chờ xác nhận',
                'detail' => number_format((int) $order->total, 0, ',', '.').'đ',
                'time' => $order->created_at,
                'url' => route('admin.orders.show', $order),
            ]);
        }

        foreach ($groups['lowStockProducts'] as $product) {
            $items->push([
                'type' => 'stock',
                'icon' => 'ri-alarm-warning-line',
                'title' => $product->name.' sắp hết hàng',
                'detail' => 'Còn '.$product->stock.' sản phẩm',
                'time' => $product->updated_at,
                'url' => route('admin.products.edit', $product),
            ]);
        }

        foreach ($groups['newContacts'] as $contact) {
            $items->push([
                'type' => 'contact',
                'icon' => 'ri-mail-unread-line',
                'title' => 'Liên hệ mới từ '.$contact->name,
                'detail' => $contact->subject ?: 'Yêu cầu hỗ trợ',
                'time' => $contact->created_at,
                'url' => route('admin.contacts.show', $contact),
            ]);
        }

        foreach ($groups['cancellationRequests'] as $request) {
            $items->push([
                'type' => 'cancel',
                'icon' => 'ri-close-circle-line',
                'title' => 'Yêu cầu hủy đơn '.($request->order?->code ?? '#'.$request->order_id),
                'detail' => $request->reason_label,
                'time' => $request->requested_at ?: $request->created_at,
                'url' => $request->order ? route('admin.orders.show', $request->order) : route('admin.orders'),
            ]);
        }

        foreach ($groups['returnRequests'] as $request) {
            $items->push([
                'type' => 'return',
                'icon' => 'ri-arrow-go-back-line',
                'title' => 'Yêu cầu '.$request->type_label.' đơn '.($request->order?->code ?? '#'.$request->order_id),
                'detail' => $request->reason_label,
                'time' => $request->requested_at ?: $request->created_at,
                'url' => $request->order ? route('admin.orders.show', $request->order) : route('admin.orders'),
            ]);
        }

        return $items
            ->sortByDesc(fn (array $item) => optional($item['time'])->timestamp ?? 0)
            ->take($limit)
            ->values();
    }

    private function lowStockQuery()
    {
        $defaultThreshold = max(0, (int) $this->settings->get('low_stock_default_threshold', 5));

        return Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($defaultThreshold) {
                $query
                    ->where(function ($inner) {
                        $inner->where('low_stock_threshold', '>', 0)
                            ->whereColumn('stock', '<=', 'low_stock_threshold');
                    })
                    ->orWhere(function ($inner) use ($defaultThreshold) {
                        $inner->where('low_stock_threshold', '<=', 0)
                            ->where('stock', '<=', $defaultThreshold);
                    });
            });
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function allowed(?User $user, string $permission): bool
    {
        return $user === null || $user->hasAdminPermission($permission);
    }
}
