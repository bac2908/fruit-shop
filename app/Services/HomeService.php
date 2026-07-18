<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;

class HomeService
{
    /**
     * Get data for homepage sections based on category slugs
     */
    public function getHomeSections(array $slugs, int $productLimit = 8)
    {
        $orderedSlugs = collect($slugs)
            ->filter(fn ($slug) => is_string($slug) && trim($slug) !== '')
            ->values();

        $configuredCategories = collect();

        if ($orderedSlugs->isNotEmpty()) {
            $slugOrderSql = "FIELD(slug, '" . implode("','", $orderedSlugs->all()) . "')";

            $configuredCategories = Category::query()
                ->whereIn('slug', $orderedSlugs->all())
                ->where('is_active', true)
                ->orderByRaw($slugOrderSql)
                ->get();
        }

        $sections = $configuredCategories
            ->map(function (Category $category) use ($productLimit) {
                return $this->buildSection($category, $productLimit);
            })
            ->values();

        $nonEmptySectionCount = $sections
            ->filter(function (array $section) {
                return $section['products']->isNotEmpty();
            })
            ->count();

        $targetNonEmptySections = max($orderedSlugs->count(), 6);

        if ($nonEmptySectionCount < $targetNonEmptySections) {
            $needed = $targetNonEmptySections - $nonEmptySectionCount;
            $usedCategoryIds = $sections
                ->pluck('category.id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $fallbackCategories = Category::query()
                ->where('is_active', true)
                ->when(!empty($usedCategoryIds), function ($query) use ($usedCategoryIds) {
                    $query->whereNotIn('id', $usedCategoryIds);
                })
                ->withCount([
                    'products as active_products_count' => function ($query) {
                        $query->where('is_active', true);
                    },
                ])
                ->orderByDesc('active_products_count')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->filter(function (Category $category) {
                    return (int) ($category->active_products_count ?? 0) > 0;
                })
                ->take($needed)
                ->values();

            $fallbackSections = $fallbackCategories
                ->map(function (Category $category) use ($productLimit) {
                    return $this->buildSection($category, $productLimit);
                })
                ->values();

            $sections = $sections->concat($fallbackSections)->values();
        }

        return $sections;
    }

    /**
     * Build one homepage section payload.
     */
    private function buildSection(Category $category, int $productLimit): array
    {
        $categoryIds = $category->descendants()
            ->pluck('id')
            ->push($category->id)
            ->unique()
            ->values();

        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('category_id', $categoryIds->all())
            ->orderByDesc('id')
            ->limit($productLimit)
            ->get();

        return [
            'category' => $category,
            'products' => $products,
            'icon_url' => $category->getIconUrl(),
            'slogan' => $this->categorySlogan($category),
        ];
    }

    private function categorySlogan(Category $category): string
    {
        $configuredSlogan = config('shop.home_category_slogans.' . $category->slug);

        if (is_string($configuredSlogan) && trim($configuredSlogan) !== '') {
            return trim($configuredSlogan);
        }

        return $category->name . ' được tuyển chọn tươi ngon mỗi ngày.';
    }

    /**
     * Get top level active categories
     */
    public function getTopCategories()
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get active coupons with images
     */
    public function getActiveCoupons(int $limit = 6, ?User $user = null)
    {
        $query = Coupon::query()
            ->with('images')
            ->valid()
            ->where('is_public', true)
            ->whereIn('code', WelcomeVoucherService::CODES);

        if ($user) {
            $email = strtolower(trim((string) $user->email));

            $query->withCount([
                'usages as current_user_usage_count' => function ($usageQuery) use ($user, $email) {
                    $usageQuery->where(function ($customerQuery) use ($user, $email) {
                        $customerQuery->where('user_id', $user->id)
                            ->orWhereRaw('LOWER(customer_email) = ?', [$email]);
                    });
                },
            ]);
        }

        return $query
            ->orderByRaw("CASE code WHEN 'GIOQUA10' THEN 1 WHEN 'QUYTTHAI1KG' THEN 2 WHEN 'KIWIVANG500' THEN 3 ELSE 4 END")
            ->limit($limit)
            ->get();
    }
}
