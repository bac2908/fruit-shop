<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductRecommendationService
{
    public function __construct(
        private readonly AprioriRecommendationService $aprioriRecommendationService
    ) {}

    public function recommend(Product $product, int $limit = 3): array
    {
        $limit = max(1, min($limit, 3));

        if (! $this->isDirectlyOrderable($product)) {
            return $this->emptyResult();
        }

        $behavioralItems = $this->aprioriRecommendationService
            ->recommendForProduct($product, $limit * 3)
            ->filter(function (array $recommendation) use ($product) {
                $recommendedProduct = $recommendation['product'] ?? null;

                return $recommendedProduct instanceof Product
                    && (int) $recommendedProduct->id !== (int) $product->id
                    && $this->isDirectlyOrderable($recommendedProduct);
            })
            ->unique(fn (array $recommendation) => (int) $recommendation['product']->id)
            ->take($limit)
            ->map(function (array $recommendation) {
                return array_merge($recommendation, ['source' => 'behavioral']);
            })
            ->values();

        $remaining = $limit - $behavioralItems->count();
        $catalogItems = $remaining > 0
            ? $this->catalogFallback(
                $product,
                $behavioralItems->pluck('product.id')->all(),
                $remaining
            )
            : collect();

        $items = $behavioralItems->concat($catalogItems)->values();
        $source = match (true) {
            $items->isEmpty() => 'none',
            $behavioralItems->isEmpty() => 'catalog',
            $catalogItems->isEmpty() => 'behavioral',
            default => 'blended',
        };

        return [
            'items' => $items,
            'source' => $source,
            'title' => match ($source) {
                'behavioral' => 'Thường được chọn cùng',
                'blended' => 'Gợi ý phù hợp cho giỏ hàng',
                default => 'Gợi ý thêm cho giỏ hàng',
            },
            'subtitle' => match ($source) {
                'behavioral' => 'Dựa trên những đơn hàng có sản phẩm này.',
                'blended' => 'Kết hợp dữ liệu mua hàng và các lựa chọn đang còn hàng.',
                default => 'Những lựa chọn có mức giá dễ kết hợp và đang còn hàng.',
            },
        ];
    }

    private function catalogFallback(Product $product, array $excludedIds, int $limit): Collection
    {
        $excludedIds[] = (int) $product->id;

        $candidates = Product::query()
            ->with(['category', 'images'])
            ->orderable()
            ->where('has_gear_detail', false)
            ->whereNotIn('id', array_values(array_unique($excludedIds)))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->filter(fn (Product $candidate) => $this->isDirectlyOrderable($candidate))
            ->values();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $salesByProduct = OrderItem::query()
            ->whereIn('product_id', $candidates->pluck('id')->all())
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', Order::STATUS_CANCELLED);
            })
            ->selectRaw('product_id, SUM(qty) as units_sold')
            ->groupBy('product_id')
            ->pluck('units_sold', 'product_id');

        $currentPrice = max(1, (int) $product->orderable_price);
        $ranked = $candidates
            ->sortByDesc(function (Product $candidate) use ($product, $currentPrice, $salesByProduct) {
                $candidatePrice = max(1, (int) $candidate->orderable_price);
                $priceDistance = abs(log($candidatePrice / $currentPrice));
                $priceFit = max(0, 100 - min(100, $priceDistance * 70));
                $categoryDiversity = (int) $candidate->category_id !== (int) $product->category_id ? 180 : 0;
                $unitsSold = min(50, (int) ($salesByProduct[$candidate->id] ?? 0));
                $catalogPriority = max(0, 100 - min(100, (int) $candidate->sort_order));

                return $categoryDiversity + ($priceFit * 4) + ($unitsSold * 12) + $catalogPriority;
            })
            ->values();

        $selected = collect();
        $usedCategories = collect([(int) $product->category_id]);

        foreach ($ranked as $candidate) {
            $categoryId = (int) $candidate->category_id;
            if ($categoryId > 0 && ! $usedCategories->contains($categoryId)) {
                $selected->push($candidate);
                $usedCategories->push($categoryId);
            }

            if ($selected->count() >= $limit) {
                break;
            }
        }

        if ($selected->count() < $limit) {
            $selected = $selected->concat(
                $ranked
                    ->whereNotIn('id', $selected->pluck('id')->all())
                    ->take($limit - $selected->count())
            );
        }

        return $selected
            ->take($limit)
            ->map(fn (Product $candidate) => [
                'product' => $candidate,
                'source' => 'catalog',
            ])
            ->values();
    }

    private function isDirectlyOrderable(Product $product): bool
    {
        return (bool) $product->is_active
            && (int) $product->stock > 0
            && (int) $product->orderable_price > 0
            && ! (bool) $product->has_gear_detail
            && ! (bool) $product->is_custom_order_product;
    }

    private function emptyResult(): array
    {
        return [
            'items' => collect(),
            'source' => 'none',
            'title' => '',
            'subtitle' => '',
        ];
    }
}
