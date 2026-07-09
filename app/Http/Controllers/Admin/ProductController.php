<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'images']);

        $keyword = trim((string) $request->query('q', ''));

        if ($keyword !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';

            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', (int) $request->query('category'));
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'hidden') {
            $query->where('is_active', false);
        }

        if ($request->query('stock') === 'out') {
            $query->where('stock', '<=', 0);
        } elseif ($request->query('stock') === 'low') {
            $query->where('stock', '>', 0)
                ->where(function ($inner) {
                    $inner->whereColumn('stock', '<=', 'low_stock_threshold')
                        ->orWhere('stock', '<=', 5);
                });
        } elseif ($request->query('stock') === 'in_stock') {
            $query->where('stock', '>', 0);
        }

        $perPage = max(10, min(50, (int) $request->query('per_page', 15)));

        $products = $query
            ->latest('updated_at')
            ->paginate($perPage)
            ->appends($request->query());

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('is_active', true)->count(),
            'hidden' => Product::query()->where('is_active', false)->count(),
            'low_stock' => Product::query()
                ->where('stock', '>', 0)
                ->where(function ($inner) {
                    $inner->whereColumn('stock', '<=', 'low_stock_threshold')
                        ->orWhere('stock', '<=', 5);
                })
                ->count(),
            'out_of_stock' => Product::query()->where('stock', '<=', 0)->count(),
        ];

        $lowStockProducts = Product::query()
            ->with('category')
            ->where('stock', '>', 0)
            ->where(function ($inner) {
                $inner->whereColumn('stock', '<=', 'low_stock_threshold')
                    ->orWhere('stock', '<=', 5);
            })
            ->orderBy('stock')
            ->take(6)
            ->get();

        return view('admin.products', compact('products', 'categories', 'stats', 'lowStockProducts'));
    }

    public function toggleVisibility(Product $product): RedirectResponse
    {
        $product->forceFill([
            'is_active' => ! $product->is_active,
        ])->save();

        return back()->with(
            'success',
            $product->is_active
                ? 'San pham da duoc hien thi lai tren storefront.'
                : 'San pham da duoc an khoi storefront.'
        );
    }
}
