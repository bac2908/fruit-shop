<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Hiển thị trang danh mục sản phẩm
     */
    public function show(Request $request, string $slug, ?string $tag = null)
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'children' => function ($q) {
                    $q->where('is_active', true)
                        ->withCount([
                            'products as active_products_count' => function ($productQuery) {
                                $productQuery->where('is_active', true)
                                    ->withOrderablePrice();
                            },
                        ])
                        ->orderBy('sort_order')
                        ->orderBy('name');
                },
            ])
            ->firstOrFail();

        $selectedTag = $tag ? urldecode($tag) : null;
        $request->merge([
            'category' => $slug,
            'tag' => $selectedTag,
        ]);

        $allCategories = $this->productService->getCategories();
        $childTags = $category->children
            ->filter(function (Category $childCategory) {
                return (int) ($childCategory->active_products_count ?? 0) > 0;
            })
            ->unique(function (Category $childCategory) {
                return Str::slug((string) $childCategory->name);
            })
            ->values();

        if ($category->slug === 'mam-dia-ngu-qua') {
            $mamDiaConfig = config('shop.mam_dia_ngu_qua', []);
            $collectionSlugs = collect($mamDiaConfig['collection_slugs'] ?? [])->filter()->values();
            $featuredSlugs = collect($mamDiaConfig['featured_slugs'] ?? [])->filter()->values();
            $typeCategorySlug = (string) ($mamDiaConfig['type_category_slug'] ?? '');
            $selectedType = trim((string) $request->get('type', ''));

            $productsQuery = Product::query()
                ->with([
                    'images' => function ($q) {
                        $q->orderBy('sort_order');
                    },
                    'category',
                ])
                ->where('is_active', true)
                ->withOrderablePrice();

            if ($collectionSlugs->isNotEmpty()) {
                $productsQuery->whereIn('slug', $collectionSlugs->all());
            } else {
                $productsQuery->where('category_id', $category->id);
            }

            if ($selectedType !== '') {
                if ($selectedType !== $typeCategorySlug) {
                    $productsQuery->whereRaw('1 = 0');
                }
            }

            if ($request->has('price')) {
                $priceFilter = (string) $request->get('price');
                if ($priceFilter === 'over-1000000') {
                    $productsQuery->where($this->orderablePriceExpression(), '>', 1000000);
                } else {
                    $range = explode('-', $priceFilter);
                    if (count($range) === 2 && is_numeric($range[0]) && is_numeric($range[1])) {
                        $productsQuery->whereBetween($this->orderablePriceExpression(), [(int) $range[0], (int) $range[1]]);
                    }
                }
            }

            switch ((string) $request->get('sort', 'default')) {
                case 'price-asc':
                    $productsQuery->orderBy($this->orderablePriceExpression(), 'asc');
                    break;
                case 'price-desc':
                    $productsQuery->orderBy($this->orderablePriceExpression(), 'desc');
                    break;
                case 'alpha-asc':
                    $productsQuery->orderBy('name', 'asc');
                    break;
                case 'alpha-desc':
                    $productsQuery->orderBy('name', 'desc');
                    break;
                default:
                    if ($collectionSlugs->isNotEmpty()) {
                        $productsQuery->orderByRaw("FIELD(slug, '" . implode("','", $collectionSlugs->all()) . "')");
                    } else {
                        $productsQuery->orderByDesc('created_at');
                    }
                    break;
            }

            $products = $productsQuery->paginate(12)->appends($request->all());

            $featuredQuery = Product::query()
                ->with([
                    'images' => function ($q) {
                        $q->orderBy('sort_order');
                    },
                    'category',
                ])
                ->where('is_active', true)
                ->withOrderablePrice();

            if ($featuredSlugs->isNotEmpty()) {
                $featuredQuery
                    ->whereIn('slug', $featuredSlugs->all())
                    ->orderByRaw("FIELD(slug, '" . implode("','", $featuredSlugs->all()) . "')");
            } else {
                $featuredQuery->orderByDesc('created_at');
            }

            $featuredProducts = $featuredQuery->limit(6)->get();

            $typeCategories = collect();
            if ($typeCategorySlug !== '') {
                $typeCategories = Category::query()
                    ->where('slug', $typeCategorySlug)
                    ->where('is_active', true)
                    ->get(['id', 'name', 'slug']);
            }

            if ($typeCategories->isEmpty()) {
                $typeCategories = collect([
                    (object) [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ],
                ]);
            }

            return view('products.mam_dia_ngu_qua', compact(
                'category',
                'products',
                'allCategories',
                'featuredProducts',
                'selectedTag',
                'childTags',
                'typeCategories',
                'selectedType'
            ));
        }

        $products = $this->productService->getCollection($request);
        $featuredProducts = $this->productService->getFeaturedProducts(5);

        return view('products.index', compact('category', 'products', 'allCategories', 'featuredProducts', 'selectedTag', 'childTags'));
    }

    private function orderablePriceExpression()
    {
        return DB::raw('CASE WHEN sale_price > 0 AND (price <= 0 OR sale_price < price) THEN sale_price ELSE price END');
    }
}
