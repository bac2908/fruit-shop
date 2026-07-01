<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function getCollection(Request $request)
    {
        $query = Product::query()
            ->with([
                'images' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->where('is_active', true)
            ->withOrderablePrice();

        // Filter by Category
        if ($request->has('category')) {
            $categorySlug = (string) $request->get('category');
            $category = Category::query()
                ->where('slug', $categorySlug)
                ->where('is_active', true)
                ->first();

            if ($category) {
                $categoryIds = $category->descendants()
                    ->pluck('id')
                    ->push($category->id)
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $query->whereIn('category_id', array_values(array_unique($categoryIds)));

                $rawTag = trim((string) $request->get('tag', ''));
                if ($rawTag !== '') {
                    $decodedTag = urldecode($rawTag);
                    $tagSlug = Str::slug($decodedTag);

                    $tagCategory = Category::query()
                        ->where('parent_id', $category->id)
                        ->where('is_active', true)
                        ->where(function ($q) use ($tagSlug, $decodedTag) {
                                                        $q->where('name', 'like', '%' . $decodedTag . '%');

                                                        if ($tagSlug !== '') {
                                                                $q->orWhere('slug', $tagSlug)
                                                                    ->orWhere('slug', 'like', '%' . $tagSlug . '%');
                                                        }
                        })
                        ->first();

                    if ($tagCategory) {
                        $tagCategoryIds = $tagCategory->descendants()
                            ->pluck('id')
                            ->push($tagCategory->id)
                            ->map(fn ($id) => (int) $id)
                            ->unique()
                            ->values()
                            ->all();

                        $hasTaggedProducts = Product::query()
                            ->where('is_active', true)
                            ->whereIn('category_id', array_values(array_unique($tagCategoryIds)))
                            ->exists();

                        if ($hasTaggedProducts) {
                            $query->whereIn('category_id', array_values(array_unique($tagCategoryIds)));
                        } else {
                            $query->where(function ($q) use ($decodedTag, $tagSlug) {
                                $q->where('name', 'like', '%' . $decodedTag . '%');

                                if ($tagSlug !== '') {
                                    $q->orWhere('slug', 'like', '%' . $tagSlug . '%');
                                }
                            });
                        }
                    } else {
                        $query->where(function ($q) use ($decodedTag, $tagSlug) {
                            $q->where('name', 'like', '%' . $decodedTag . '%');

                            if ($tagSlug !== '') {
                                $q->orWhere('slug', 'like', '%' . $tagSlug . '%');
                            }
                        });
                    }
                }
            }
        }

        // Filter by Price Range
        if ($request->has('price')) {
            $range = explode('-', $request->price);
            if (count($range) == 2) {
                $query->whereBetween($this->orderablePriceExpression(), [$range[0], $range[1]]);
            } elseif ($request->price == 'over-1000000') {
                $query->where($this->orderablePriceExpression(), '>', 1000000);
            }
        }

        // Sort
        switch ($request->get('sort')) {
            case 'price-asc':
                $query->orderBy($this->orderablePriceExpression(), 'asc');
                break;
            case 'price-desc':
                $query->orderBy($this->orderablePriceExpression(), 'desc');
                break;
            case 'alpha-asc':
                $query->orderBy('name', 'asc');
                break;
            case 'alpha-desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query->paginate(12)->appends($request->all());
    }

    public function find($id)
    {
        return Product::where('is_active', true)->find($id);
    }

    public function findBySlug(string $slug)
    {
        return Product::query()
            ->with([
                'category',
                'images' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->first();
    }

    public function search($query)
    {
        $query = trim((string) $query);
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';
        $prefixLike = str_replace(['%', '_'], ['\%', '\_'], $query) . '%';

        return Product::query()
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
            ->paginate(12)
            ->appends(['q' => $query]);
    }

    public function getCategories()
    {
        return Category::where('is_active', true)
            ->withCount('products')
            ->get();
    }

    public function getFeaturedProducts($limit = 5)
    {
        return Product::query()
            ->where('is_active', true)
            ->withOrderablePrice()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRelatedProducts(Product $product, int $limit = 8)
    {
        $baseQuery = Product::query()
            ->where('is_active', true)
            ->withOrderablePrice()
            ->where('id', '!=', $product->id);

        if ($product->category_id) {
            $related = (clone $baseQuery)
                ->where('category_id', $product->category_id)
                ->orderByDesc('id')
                ->limit($limit)
                ->get();

            if ($related->count() >= $limit) {
                return $related;
            }

            $excludeIds = $related->pluck('id')->push($product->id)->all();

            $extra = Product::query()
                ->where('is_active', true)
                ->withOrderablePrice()
                ->whereNotIn('id', $excludeIds)
                ->orderByDesc('id')
                ->limit($limit - $related->count())
                ->get();

            return $related->concat($extra);
        }

        return $baseQuery
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function getOptionProducts(Product $product, int $limit = 8)
    {
        $baseName = $this->normalizeProductBaseName($product->name);

        if ($baseName === '') {
            return collect([$product]);
        }

        $query = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id);

        if ($product->category_id) {
            $query->where('category_id', $product->category_id);
        }

        $slugNeedle = Str::slug($baseName);

        $query->where(function ($q) use ($baseName, $slugNeedle) {
            $q->where('name', 'like', '%' . $baseName . '%');

            if ($slugNeedle !== '') {
                $q->orWhere('slug', 'like', $slugNeedle . '%');
            }
        });

        $options = $query
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return collect([$product])
            ->concat($options)
            ->unique('id')
            ->values();
    }

    private function orderablePriceExpression()
    {
        return DB::raw('CASE WHEN sale_price > 0 AND (price <= 0 OR sale_price < price) THEN sale_price ELSE price END');
    }

    private function normalizeProductBaseName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '';
        }

        $baseName = trim((string) preg_replace('/\s*-\s*[^-]+$/u', '', $name));

        return $baseName !== '' ? $baseName : $name;
    }
}
