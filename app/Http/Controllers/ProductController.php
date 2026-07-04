<?php

namespace App\Http\Controllers;

use App\Models\ProductView;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\WishlistItem;
use App\Services\AprioriRecommendationService;
use App\Services\ProductService;
use App\Support\MediaUrl;
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

    public function quickView($slug)
    {
        $product = $this->productService->findBySlug($slug);

        if (!$product) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm.',
            ], 404);
        }

        $images = collect([$product->primary_image_url])
            ->merge($product->images->pluck('url')->map(function ($url) {
                $url = trim((string) $url);

                return $url !== '' ? MediaUrl::resolve($url) : null;
            }))
            ->filter()
            ->unique()
            ->values();

        if ($images->isEmpty()) {
            $images = collect(['//theme.hstatic.net/200000157781/1001036201/14/no-image.jpg?v=1064']);
        }

        $basePrice = (int) ($product->price ?? 0);
        $salePrice = (int) ($product->sale_price ?? 0);
        $displayPrice = (int) $product->orderable_price;
        $isSalePrice = $basePrice > 0 && $salePrice > 0 && $salePrice < $basePrice;
        $discountPercent = $isSalePrice ? (int) round((($basePrice - $salePrice) / $basePrice) * 100) : 0;
        $isCustomOrder = (bool) $product->is_custom_order_product;
        $inStock = (int) $product->stock > 0;
        $canAddToCart = (bool) $product->is_active
            && $inStock
            && $displayPrice > 0
            && !$isCustomOrder
            && !(bool) $product->has_gear_detail;

        $descriptionText = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($product->short_desc ?: $product->description))));

        if ($descriptionText === '') {
            $descriptionText = 'Thông tin sản phẩm đang được cập nhật.';
        }

        $category = $product->category;
        $detailUrl = route('products.show', $product->slug);
        $consultUrl = route('contact.page', ['product' => $product->name]);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'url' => $detailUrl,
            'category' => [
                'name' => optional($category)->name ?: 'Sản phẩm',
                'url' => $category ? route('categories.show', $category->slug) : route('products.index'),
            ],
            'images' => $images->all(),
            'price' => [
                'value' => $displayPrice,
                'formatted' => $displayPrice > 0
                    ? ($isCustomOrder ? 'Từ ' : '') . number_format($displayPrice, 0, ',', '.') . 'đ'
                    : 'Đang cập nhật giá',
                'compare_formatted' => $isSalePrice ? number_format($basePrice, 0, ',', '.') . 'đ' : null,
                'discount_percent' => $discountPercent,
                'is_contact_price' => $displayPrice <= 0,
            ],
            'stock' => [
                'quantity' => (int) $product->stock,
                'in_stock' => $inStock,
                'label' => $inStock ? 'Còn hàng' : 'Tạm hết hàng',
            ],
            'sku' => trim((string) ($product->sku ?? '')),
            'unit' => trim((string) ($product->unit ?? '')),
            'description' => Str::limit($descriptionText, 520),
            'manufacturer' => 'Khác',
            'can_add_to_cart' => $canAddToCart,
            'is_custom_order' => $isCustomOrder,
            'has_gear_detail' => (bool) $product->has_gear_detail,
            'primary_action' => [
                'url' => $isCustomOrder ? $consultUrl : $detailUrl,
                'label' => $isCustomOrder
                    ? 'Liên hệ tư vấn'
                    : ((bool) $product->has_gear_detail ? 'Chọn sản phẩm' : 'Xem chi tiết'),
            ],
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
