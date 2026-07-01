<?php

namespace App\Http\Controllers;

use App\Models\ProductView;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\WishlistItem;
use App\Services\AprioriRecommendationService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected $productService;
    protected $aprioriRecommendationService;

    public function __construct(ProductService $productService, AprioriRecommendationService $aprioriRecommendationService)
    {
        $this->productService = $productService;
        $this->aprioriRecommendationService = $aprioriRecommendationService;
    }

    public function index(Request $request)
    {
        $products = $this->productService->getCollection($request);
        $allCategories = $this->productService->getCategories();
        $featuredProducts = $this->productService->getFeaturedProducts(5);

        return view('products.index', [
            'products'          => $products,
            'allCategories'     => $allCategories,
            'featuredProducts'  => $featuredProducts,
        ]);
    }

    public function show($slug)
    {
        $product = $this->productService->findBySlug($slug);

        if (!$product) {
            abort(404);
        }

        $this->recordProductView($product->id);

        $relatedProducts = $this->productService->getRelatedProducts($product, 8);
        $optionProducts = $product->has_gear_detail
            ? $this->productService->getOptionProducts($product, 8)
            : collect([$product]);
        $featuredProducts = $this->productService->getFeaturedProducts(5);
        $frequentlyBoughtTogether = $this->aprioriRecommendationService->recommendForProduct($product, 4);
        $aprioriStats = $this->aprioriRecommendationService->getStats();
        $isWishlisted = $this->isWishlisted($product->id);

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'optionProducts' => $optionProducts,
            'featuredProducts' => $featuredProducts,
            'frequentlyBoughtTogether' => $frequentlyBoughtTogether,
            'aprioriStats' => $aprioriStats,
            'isWishlisted' => $isWishlisted,
        ]);
    }

    public function search(Request $request)
    {
        $keyword = trim((string) $request->get('q', ''));

        if ($keyword === '') {
            return redirect()->route('products.index');
        }

        $products = $this->productService->search($keyword);
        $this->recordSearchKeyword($keyword, method_exists($products, 'total') ? (int) $products->total() : 0);
        $allCategories = $this->productService->getCategories();
        $featuredProducts = $this->productService->getFeaturedProducts(5);

        return view('products.index', [
            'products' => $products,
            'allCategories' => $allCategories,
            'featuredProducts' => $featuredProducts,
            'searchKeyword' => $keyword,
        ]);
    }

    public function suggestions(Request $request)
    {
        $keyword = $this->cleanSearchKeyword((string) $request->get('q', ''));
        $products = collect();

        if (Str::length($keyword) >= 2) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';
            $prefixLike = str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';

            $products = Product::query()
                ->with([
                    'category',
                    'images' => function ($q) {
                        $q->orderBy('sort_order');
                    },
                ])
                ->where('is_active', true)
                ->withOrderablePrice()
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhereHas('category', function ($categoryQuery) use ($like) {
                            $categoryQuery->where('name', 'like', $like)
                                ->orWhere('slug', 'like', $like);
                        });
                })
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', [$prefixLike, $like])
                ->orderByDesc('id')
                ->limit(6)
                ->get();
        }

        return response()->json([
            'keyword' => $keyword,
            'products' => $products->map(function (Product $product) {
                return $this->formatSearchSuggestionProduct($product);
            })->values(),
            'recent' => $this->recentSearchKeywords(),
            'popular' => $this->popularSearchKeywords(),
        ]);
    }

    private function recordProductView(int $productId): void
    {
        if (!auth()->check() || !Schema::hasTable('product_views')) {
            return;
        }

        $view = ProductView::query()->firstOrCreate([
            'user_id' => auth()->id(),
            'product_id' => $productId,
        ], [
            'view_count' => 0,
            'last_viewed_at' => now(),
        ]);

        $view->increment('view_count');
        $view->forceFill(['last_viewed_at' => now()])->save();
    }

    private function isWishlisted(int $productId): bool
    {
        if (!auth()->check() || !Schema::hasTable('wishlist_items')) {
            return false;
        }

        return WishlistItem::query()
            ->where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->exists();
    }

    private function recordSearchKeyword(string $keyword, int $resultsCount): void
    {
        if (!auth()->check() || !Schema::hasTable('search_histories')) {
            return;
        }

        $keyword = $this->cleanSearchKeyword($keyword);
        $normalized = $this->normalizeSearchKeyword($keyword);

        if ($keyword === '' || $normalized === '') {
            return;
        }

        SearchHistory::query()->updateOrCreate([
            'user_id' => auth()->id(),
            'keyword_normalized' => $normalized,
        ], [
            'keyword' => $keyword,
            'results_count' => $resultsCount,
            'last_searched_at' => now(),
        ]);
    }

    private function recentSearchKeywords(): array
    {
        if (!auth()->check() || !Schema::hasTable('search_histories')) {
            return [];
        }

        return SearchHistory::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('last_searched_at')
            ->limit(6)
            ->pluck('keyword')
            ->all();
    }

    private function popularSearchKeywords(): array
    {
        $fallback = ['măng cụt', 'giỏ quà', 'cherry', 'sầu riêng', 'nho xanh'];

        if (!Schema::hasTable('search_histories')) {
            return $fallback;
        }

        $keywords = SearchHistory::query()
            ->selectRaw('MIN(keyword) as keyword, COUNT(*) as search_count, MAX(last_searched_at) as last_seen_at')
            ->groupBy('keyword_normalized')
            ->orderByDesc('search_count')
            ->orderByDesc('last_seen_at')
            ->limit(6)
            ->pluck('keyword')
            ->filter()
            ->values()
            ->all();

        return $keywords ?: $fallback;
    }

    private function formatSearchSuggestionProduct(Product $product): array
    {
        $price = (int) $product->orderable_price;
        $isCustomOrder = (bool) $product->is_custom_order_product;

        return [
            'name' => $product->name,
            'url' => route('products.show', $product->slug),
            'image' => $product->primary_image_url,
            'category' => optional($product->category)->name,
            'price' => $price > 0
                ? ($isCustomOrder ? 'Từ ' : '') . number_format($price, 0, ',', '.') . '₫'
                : 'Liên hệ',
        ];
    }

    private function cleanSearchKeyword(string $keyword): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $keyword));
    }

    private function normalizeSearchKeyword(string $keyword): string
    {
        return Str::lower(Str::ascii($this->cleanSearchKeyword($keyword)));
    }
}
