<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->query($request);
        $user = $request->user();

        return view('admin.search', [
            'query' => $query,
            'products' => $query !== '' && $user->hasAdminPermission('catalog.manage') ? $this->products($query, 20) : collect(),
            'orders' => $query !== '' && $user->hasAdminPermission('orders.view') ? $this->orders($query, 20) : collect(),
            'customers' => $query !== '' && $user->hasAdminPermission('customers.manage') ? $this->customers($query, 20) : collect(),
            'contacts' => $query !== '' && $user->hasAdminPermission('support.manage') ? $this->contacts($query, 20) : collect(),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $this->query($request);
        if (mb_strlen($query) < 2) {
            return response()->json(['items' => []]);
        }

        $user = $request->user();
        $items = collect();

        if ($user->hasAdminPermission('orders.view')) {
            $items = $items->concat($this->orders($query, 3)->map(fn (Order $order) => [
                'type' => 'Đơn hàng',
                'title' => $order->code,
                'detail' => $order->customer_name.' · '.number_format((int) $order->total, 0, ',', '.').'đ',
                'url' => route('admin.orders.show', $order),
            ]));
        }

        if ($user->hasAdminPermission('catalog.manage')) {
            $items = $items->concat($this->products($query, 4)->map(fn (Product $product) => [
                'type' => 'Sản phẩm',
                'title' => $product->name,
                'detail' => ($product->sku ?: 'Chưa có SKU').' · tồn '.$product->stock,
                'url' => route('admin.products.edit', $product),
            ]));
        }

        if ($user->hasAdminPermission('customers.manage')) {
            $items = $items->concat($this->customers($query, 3)->map(fn (User $customer) => [
                'type' => 'Khách hàng',
                'title' => $customer->name,
                'detail' => $customer->email,
                'url' => route('admin.customers.show', $customer),
            ]));
        }

        $items = $items
            ->take(10)
            ->values();

        return response()->json(['items' => $items]);
    }

    private function products(string $query, int $limit)
    {
        return Product::withTrashed()
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('slug', 'like', '%'.$query.'%')
                    ->orWhere('sku', 'like', '%'.$query.'%');
            })
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    private function orders(string $query, int $limit)
    {
        return Order::query()
            ->where(function ($builder) use ($query) {
                $builder->where('code', 'like', '%'.$query.'%')
                    ->orWhere('customer_name', 'like', '%'.$query.'%')
                    ->orWhere('customer_email', 'like', '%'.$query.'%')
                    ->orWhere('customer_phone', 'like', '%'.$query.'%')
                    ->orWhere('tracking_code', 'like', '%'.$query.'%');
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function customers(string $query, int $limit)
    {
        return User::query()
            ->where('role', 'customer')
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->orWhere('phone', 'like', '%'.$query.'%');
            })
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    private function contacts(string $query, int $limit)
    {
        return ContactMessage::query()
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->orWhere('phone', 'like', '%'.$query.'%')
                    ->orWhere('subject', 'like', '%'.$query.'%');
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function query(Request $request): string
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return trim((string) ($validated['q'] ?? ''));
    }
}
