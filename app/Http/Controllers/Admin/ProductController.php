<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AdminProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->with(['category', 'images']);

        if ($request->query('status') === 'trashed') {
            $query->onlyTrashed();
        }

        $keyword = trim((string) $request->query('q', ''));
        if ($keyword !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $keyword).'%';
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
        $products = $query->latest('updated_at')->paginate($perPage)->appends($request->query());
        $categories = $this->categories();
        $stats = [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('is_active', true)->count(),
            'hidden' => Product::query()->where('is_active', false)->count(),
            'deleted' => Product::onlyTrashed()->count(),
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

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product([
                'unit' => 'kg',
                'stock' => 0,
                'low_stock_threshold' => 5,
                'sort_order' => 0,
                'is_active' => false,
                'has_gear_detail' => false,
            ]),
            'categories' => $this->categories(),
        ]);
    }

    public function store(UpsertProductRequest $request, AdminProductService $products): RedirectResponse
    {
        $product = $products->create(
            $request->validated(),
            $request->file('images', []),
            $this->auditContext($request)
        );

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Đã tạo sản phẩm và ghi nhận tồn kho ban đầu.');
    }

    public function edit(Product $product): View
    {
        $product->load(['category', 'images']);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categories(),
        ]);
    }

    public function update(
        UpsertProductRequest $request,
        Product $product,
        AdminProductService $products
    ): RedirectResponse {
        $product = $products->update(
            $product,
            $request->validated(),
            $request->file('images', []),
            $this->auditContext($request)
        );

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Đã cập nhật sản phẩm, hình ảnh và tồn kho.');
    }

    public function toggleVisibility(
        Request $request,
        Product $product,
        AdminProductService $products
    ): RedirectResponse {
        $product = $products->toggleVisibility($product, $this->auditContext($request));

        return back()->with(
            'success',
            $product->is_active
                ? 'Sản phẩm đã được hiển thị lại trên storefront.'
                : 'Sản phẩm đã được ẩn khỏi storefront.'
        );
    }

    public function destroy(
        Request $request,
        Product $product,
        AdminProductService $products
    ): RedirectResponse {
        $products->delete($product, $this->auditContext($request));

        return redirect()->route('admin.products')->with('success', 'Đã đưa sản phẩm vào thùng rác.');
    }

    public function restore(
        Request $request,
        int $product,
        AdminProductService $products
    ): RedirectResponse {
        $restored = $products->restore($product, $this->auditContext($request));

        return redirect()
            ->route('admin.products.edit', $restored)
            ->with('success', 'Đã khôi phục sản phẩm ở trạng thái tạm ẩn để kiểm tra lại.');
    }

    public function destroyImage(
        Request $request,
        Product $product,
        ProductImage $image,
        AdminProductService $products
    ): RedirectResponse {
        $products->deleteImage($product, $image, $this->auditContext($request));

        return back()->with('success', 'Đã xóa ảnh sản phẩm.');
    }

    private function categories()
    {
        return Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function auditContext(Request $request): array
    {
        return [
            'user_id' => optional($request->user())->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ];
    }
}
