<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AprioriRecommendationService
{
    // Thông số mặc định cho Apriori
    private $minSupport = 0.02;      // Tối thiểu 2% giao dịch
    private $minConfidence = 0.30;   // Tối thiểu 30% độ tin cậy
    private $minLift = 1.0;          // Tối thiểu 1.0 để có liên quan
    private $minPairCount = 2;       // Tối thiểu 2 lần xuất hiện
    private $maxItemsetSize = 4;     // Tìm kiếm itemsets tới 4-items
    private $cacheHours = 24;

    private $analysis = null;
    private $enableDebug = false;

    public function __construct(
        $minSupport = 0.02,
        $minConfidence = 0.30,
        $minLift = 1.0,
        $minPairCount = 2,
        $maxItemsetSize = 4
    ) {
        $this->minSupport = $minSupport;
        $this->minConfidence = $minConfidence;
        $this->minLift = $minLift;
        $this->minPairCount = $minPairCount;
        $this->maxItemsetSize = min($maxItemsetSize, 5); // Giới hạn tối đa 5-items
    }

    /**
     * Lấy sản phẩm được gợi ý dựa trên quy tắc Apriori
     */
    public function recommendForProduct(Product $product, int $limit = 4): Collection
    {
        $analysis = $this->analyzeTransactions();
        $rules = collect($analysis['rules_2items'] ?? [])
            ->where('antecedent_id', (int) $product->id)
            ->filter(function (array $rule) {
                return $this->passesThresholds($rule);
            })
            ->sortByDesc(function (array $rule) {
                return ($rule['lift'] * 1000000) + ($rule['confidence'] * 1000) + $rule['support'];
            })
            ->take($limit)
            ->values();

        if ($rules->isEmpty()) {
            return collect();
        }

        $productIds = $rules->pluck('consequent_id')->unique()->values()->all();
        $products = Product::query()
            ->with([
                'images' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return $rules->map(function (array $rule) use ($products) {
            $recommendedProduct = $products->get($rule['consequent_id']);

            if (!$recommendedProduct) {
                return null;
            }

            return [
                'product' => $recommendedProduct,
                'support' => $rule['support'],
                'confidence' => $rule['confidence'],
                'lift' => $rule['lift'],
                'pair_count' => $rule['pair_count'],
                'antecedent_count' => $rule['antecedent_count'],
                'transaction_count' => $rule['transaction_count'],
            ];
        })->filter()->values();
    }

    /**
     * Lấy thống kê chi tiết cho báo cáo
     */
    public function getStats(): array
    {
        $analysis = $this->analyzeTransactions();

        return [
            // Thông tin giao dịch
            'orders_count' => $analysis['orders_count'],
            'transaction_count' => $analysis['transaction_count'],
            'avg_items_per_transaction' => $analysis['transaction_count'] > 0
                ? number_format(array_sum($analysis['transaction_sizes']) / $analysis['transaction_count'], 2)
                : 0,

            // Thống kê itemsets
            'unique_products' => count($analysis['item_counts']),
            'total_unique_itemsets_2items' => count($analysis['itemsets_2items']),
            'total_unique_itemsets_3items' => count($analysis['itemsets_3items']),
            'total_unique_itemsets_4items' => count($analysis['itemsets_4items']),

            // Thống kê quy tắc
            'rules_count_2items' => count($analysis['rules_2items']),
            'rules_count_3items' => count($analysis['rules_3items']),
            'rules_count_4items' => count($analysis['rules_4items']),
            'total_rules_count' => count($analysis['rules_2items'])
                + count($analysis['rules_3items'])
                + count($analysis['rules_4items']),
            'rules_count' => count($analysis['rules_2items'])
                + count($analysis['rules_3items'])
                + count($analysis['rules_4items']),

            // Ngưỡng cấu hình
            'min_support' => $this->minSupport,
            'min_confidence' => $this->minConfidence,
            'min_lift' => $this->minLift,
            'min_pair_count' => $this->minPairCount,

            // Thống kê chi tiết hơn
            'top_products' => $this->getTopProducts($analysis),
            'strong_rules' => $this->getStrongRules($analysis),
            'avg_support' => $this->calculateAverageMetric($analysis['rules_2items'], 'support'),
            'avg_confidence' => $this->calculateAverageMetric($analysis['rules_2items'], 'confidence'),
            'avg_lift' => $this->calculateAverageMetric($analysis['rules_2items'], 'lift'),
        ];
    }

    /**
     * Lấy tất cả quy tắc với filter
     */
    public function getAllRules(
        ?float $minSupport = null,
        ?float $minConfidence = null,
        ?float $minLift = null,
        int $limit = 100
    ): Collection {
        $minSupport = $minSupport ?? $this->minSupport;
        $minConfidence = $minConfidence ?? $this->minConfidence;
        $minLift = $minLift ?? $this->minLift;

        $analysis = $this->analyzeTransactions();
        $allRules = array_merge(
            $analysis['rules_2items'],
            $analysis['rules_3items'],
            $analysis['rules_4items']
        );

        return collect($allRules)
            ->filter(function (array $rule) use ($minSupport, $minConfidence, $minLift) {
                return $rule['support'] >= $minSupport
                    && $rule['confidence'] >= $minConfidence
                    && $rule['lift'] >= $minLift;
            })
            ->sortByDesc(function (array $rule) {
                return ($rule['lift'] * 1000000) + ($rule['confidence'] * 1000) + $rule['support'];
            })
            ->take($limit)
            ->values();
    }

    /**
     * Lấy frequent itemsets
     */
    public function getFrequentItemsets(int $itemsetSize = 2): Collection
    {
        $analysis = $this->analyzeTransactions();

        switch ($itemsetSize) {
            case 2:
                $itemsets = $analysis['itemsets_2items'];
                break;
            case 3:
                $itemsets = $analysis['itemsets_3items'];
                break;
            case 4:
                $itemsets = $analysis['itemsets_4items'];
                break;
            default:
                $itemsets = [];
        }

        return collect($itemsets)
            ->sortByDesc('support')
            ->values();
    }

    /**
     * Phân tích giao dịch sử dụng Apriori
     */
    private function analyzeTransactions(): array
    {
        if ($this->analysis !== null) {
            return $this->analysis;
        }

        // Thử lấy từ cache
        $cacheKey = $this->getCacheKey();
        if (Cache::has($cacheKey)) {
            return $this->analysis = Cache::get($cacheKey);
        }

        // Lấy dữ liệu đơn hàng
        $orders = Order::query()
            ->with(['items' => function ($q) {
                $q->select('id', 'order_id', 'product_id')
                    ->whereNotNull('product_id');
            }])
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->get(['id', 'status']);

        // Xây dựng danh sách giao dịch
        $transactions = [];
        $transactionSizes = [];

        foreach ($orders as $order) {
            $productIds = $order->items
                ->pluck('product_id')
                ->map(function ($productId) {
                    return (int) $productId;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            if (count($productIds) >= 2) {
                $transactions[] = $productIds;
                $transactionSizes[] = count($productIds);
            }
        }

        $transactionCount = count($transactions);

        // Bước 1: Tính toán 1-itemsets (single items)
        $itemCounts = [];
        foreach ($transactions as $productIds) {
            foreach ($productIds as $productId) {
                $itemCounts[$productId] = ($itemCounts[$productId] ?? 0) + 1;
            }
        }

        // Bước 2: Sinh itemsets và quy tắc
        $analysis = [
            'orders_count' => $orders->count(),
            'transaction_count' => $transactionCount,
            'transaction_sizes' => $transactionSizes,
            'item_counts' => $itemCounts,
            'itemsets_2items' => [],
            'itemsets_3items' => [],
            'itemsets_4items' => [],
            'rules_2items' => [],
            'rules_3items' => [],
            'rules_4items' => [],
        ];

        $itemsetCountMap = [];
        foreach ($itemCounts as $productId => $count) {
            $itemsetCountMap[$this->itemsetKey([(int) $productId])] = (int) $count;
        }

        if ($transactionCount > 0) {
            // Tạo 2-itemsets
            $analysis['itemsets_2items'] = $this->generateItemsets($transactions, 2, $itemCounts, $transactionCount);
            $this->appendItemsetCounts($analysis['itemsets_2items'], $itemsetCountMap);

            // Tạo 3-itemsets nếu được bật
            if ($this->maxItemsetSize >= 3) {
                $analysis['itemsets_3items'] = $this->generateItemsets($transactions, 3, $itemCounts, $transactionCount);
                $this->appendItemsetCounts($analysis['itemsets_3items'], $itemsetCountMap);
            }

            // Tạo 4-itemsets nếu được bật
            if ($this->maxItemsetSize >= 4) {
                $analysis['itemsets_4items'] = $this->generateItemsets($transactions, 4, $itemCounts, $transactionCount);
                $this->appendItemsetCounts($analysis['itemsets_4items'], $itemsetCountMap);
            }

            $analysis['rules_2items'] = $this->buildRulesForItemsets(
                $analysis['itemsets_2items'],
                $itemsetCountMap,
                $transactionCount
            );
            $analysis['rules_3items'] = $this->buildRulesForItemsets(
                $analysis['itemsets_3items'],
                $itemsetCountMap,
                $transactionCount
            );
            $analysis['rules_4items'] = $this->buildRulesForItemsets(
                $analysis['itemsets_4items'],
                $itemsetCountMap,
                $transactionCount
            );
        }

        // Lọc quy tắc theo ngưỡng
        $analysis['rules_2items'] = array_values(array_filter(
            $analysis['rules_2items'],
            function($rule) { return $this->passesThresholds($rule); }
        ));
        $analysis['rules_3items'] = array_values(array_filter(
            $analysis['rules_3items'],
            function($rule) { return $this->passesThresholds($rule); }
        ));
        $analysis['rules_4items'] = array_values(array_filter(
            $analysis['rules_4items'],
            function($rule) { return $this->passesThresholds($rule); }
        ));

        // Lưu vào cache
        Cache::put($cacheKey, $analysis, now()->addHours($this->cacheHours));

        return $this->analysis = $analysis;
    }

    /**
     * Sinh itemsets với độ dài cụ thể
     */
    private function generateItemsets(array $transactions, int $k, array $itemCounts, int $transactionCount): array
    {
        $itemsets = [];

        if ($k === 2) {
            // Sinh 2-itemsets
            $pairCounts = [];
            foreach ($transactions as $productIds) {
                $count = count($productIds);
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $key = $this->itemsetKey([$productIds[$i], $productIds[$j]]);
                        $pairCounts[$key] = ($pairCounts[$key] ?? 0) + 1;
                    }
                }
            }

            foreach ($pairCounts as $key => $count) {
                if ($count >= $this->minPairCount && ($count / $transactionCount) >= $this->minSupport) {
                    $ids = explode(':', $key);
                    $itemsets[] = [
                        'items' => [intval($ids[0]), intval($ids[1])],
                        'count' => $count,
                        'support' => $count / $transactionCount,
                    ];
                }
            }
        } else if ($k === 3) {
            // Sinh 3-itemsets từ 2-itemsets
            $tripleCounts = [];
            foreach ($transactions as $productIds) {
                $count = count($productIds);
                if ($count >= 3) {
                    for ($i = 0; $i < $count; $i++) {
                        for ($j = $i + 1; $j < $count; $j++) {
                            for ($m = $j + 1; $m < $count; $m++) {
                                $key = $this->itemsetKey([$productIds[$i], $productIds[$j], $productIds[$m]]);
                                $tripleCounts[$key] = ($tripleCounts[$key] ?? 0) + 1;
                            }
                        }
                    }
                }
            }

            foreach ($tripleCounts as $key => $count) {
                $support = $count / $transactionCount;
                if ($count >= $this->minPairCount && $support >= $this->minSupport) {
                    $ids = explode(':', $key);
                    $itemsets[] = [
                        'items' => array_map('intval', $ids),
                        'count' => $count,
                        'support' => $support,
                    ];
                }
            }
        } else if ($k === 4) {
            // Sinh 4-itemsets từ 3-itemsets
            $quadCounts = [];
            foreach ($transactions as $productIds) {
                $count = count($productIds);
                if ($count >= 4) {
                    for ($i = 0; $i < $count; $i++) {
                        for ($j = $i + 1; $j < $count; $j++) {
                            for ($m = $j + 1; $m < $count; $m++) {
                                for ($n = $m + 1; $n < $count; $n++) {
                                    $key = $this->itemsetKey([$productIds[$i], $productIds[$j], $productIds[$m], $productIds[$n]]);
                                    $quadCounts[$key] = ($quadCounts[$key] ?? 0) + 1;
                                }
                            }
                        }
                    }
                }
            }

            foreach ($quadCounts as $key => $count) {
                $support = $count / $transactionCount;
                if ($count >= $this->minPairCount && $support >= $this->minSupport) {
                    $ids = explode(':', $key);
                    $itemsets[] = [
                        'items' => array_map('intval', $ids),
                        'count' => $count,
                        'support' => $support,
                    ];
                }
            }
        }

        return $itemsets;
    }

    /**
     * Gom count của itemsets vào bản đồ tra cứu
     */
    private function appendItemsetCounts(array $itemsets, array &$itemsetCountMap): void
    {
        foreach ($itemsets as $itemset) {
            if (!isset($itemset['items'], $itemset['count'])) {
                continue;
            }

            $itemsetCountMap[$this->itemsetKey($itemset['items'])] = (int) $itemset['count'];
        }
    }

    /**
     * Sinh toàn bộ quy tắc từ danh sách itemsets
     */
    private function buildRulesForItemsets(array $itemsets, array $itemsetCountMap, int $transactionCount): array
    {
        $rules = [];

        foreach ($itemsets as $itemset) {
            if (!isset($itemset['items'], $itemset['count'])) {
                continue;
            }

            $rules = array_merge(
                $rules,
                $this->buildRulesFromItemset($itemset['items'], (int) $itemset['count'], $itemsetCountMap, $transactionCount)
            );
        }

        return $rules;
    }

    /**
     * Sinh quy tắc từ một itemset cụ thể
     */
    private function buildRulesFromItemset(array $items, int $itemsetCount, array $itemsetCountMap, int $transactionCount): array
    {
        if ($transactionCount <= 0 || $itemsetCount <= 0) {
            return [];
        }

        sort($items);
        $support = $itemsetCount / $transactionCount;
        $rules = [];

        foreach ($this->generateNonEmptyProperSubsets($items) as $antecedent) {
            $consequent = array_values(array_diff($items, $antecedent));
            sort($consequent);

            $antecedentKey = $this->itemsetKey($antecedent);
            $consequentKey = $this->itemsetKey($consequent);
            $antecedentCount = (int) ($itemsetCountMap[$antecedentKey] ?? 0);
            $consequentCount = (int) ($itemsetCountMap[$consequentKey] ?? 0);

            if ($antecedentCount <= 0 || $consequentCount <= 0) {
                continue;
            }

            $confidence = $itemsetCount / $antecedentCount;
            $consequentSupport = $consequentCount / $transactionCount;
            $lift = $consequentSupport > 0 ? $confidence / $consequentSupport : 0;

            $rules[] = [
                'antecedent_id' => $this->formatItemsetLabel($antecedent),
                'consequent_id' => $this->formatItemsetLabel($consequent),
                'support' => round($support, 4),
                'confidence' => round($confidence, 4),
                'lift' => round($lift, 4),
                'pair_count' => $itemsetCount,
                'antecedent_count' => $antecedentCount,
                'consequent_count' => $consequentCount,
                'transaction_count' => $transactionCount,
            ];
        }

        return $rules;
    }

    /**
     * Sinh tập con khác rỗng và không phải toàn bộ itemset
     */
    private function generateNonEmptyProperSubsets(array $items): array
    {
        $items = array_values($items);
        $count = count($items);
        $subsets = [];

        if ($count <= 1) {
            return $subsets;
        }

        $maxMask = (1 << $count) - 1;
        for ($mask = 1; $mask < $maxMask; $mask++) {
            $subset = [];
            for ($i = 0; $i < $count; $i++) {
                if ($mask & (1 << $i)) {
                    $subset[] = $items[$i];
                }
            }

            if (!empty($subset) && count($subset) < $count) {
                $subsets[] = $subset;
            }
        }

        return $subsets;
    }

    /**
     * Format itemset thành nhãn đơn/ghép
     */
    private function formatItemsetLabel(array $items)
    {
        if (count($items) === 1) {
            return (int) $items[0];
        }

        sort($items);
        return implode(':', $items);
    }

    /**
     * Kiểm tra quy tắc có đạt ngưỡng hay không
     */
    private function passesThresholds(array $rule): bool
    {
        return $rule['support'] >= $this->minSupport
            && $rule['confidence'] >= $this->minConfidence
            && $rule['lift'] >= $this->minLift;
    }

    /**
     * Tạo key cho itemset
     */
    private function itemsetKey(array $ids): string
    {
        sort($ids);
        return implode(':', $ids);
    }

    /**
     * Tạo key cho cache
     */
    private function getCacheKey(): string
    {
        return 'apriori_analysis_' . md5(
            $this->minSupport . '_' .
            $this->minConfidence . '_' .
            $this->minLift . '_' .
            $this->minPairCount . '_' .
            $this->maxItemsetSize
        );
    }

    /**
     * Lấy sản phẩm phổ biến nhất
     */
    private function getTopProducts(array $analysis, int $limit = 10): array
    {
        $itemCounts = $analysis['item_counts'];
        arsort($itemCounts);

        $topIds = array_slice(array_keys($itemCounts), 0, $limit);
        return Product::query()
            ->whereIn('id', $topIds)
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Lấy quy tắc mạnh nhất
     */
    private function getStrongRules(array $analysis, int $limit = 5): array
    {
        $rules = array_merge(
            $analysis['rules_2items'],
            $analysis['rules_3items'],
            $analysis['rules_4items']
        );

        usort($rules, function ($a, $b) {
            $scoreA = ($a['lift'] * 1000000) + ($a['confidence'] * 1000) + $a['support'];
            $scoreB = ($b['lift'] * 1000000) + ($b['confidence'] * 1000) + $b['support'];
            return $scoreB <=> $scoreA;
        });

        return array_slice($rules, 0, $limit);
    }

    /**
     * Tính toán giá trị trung bình của một metric
     */
    private function calculateAverageMetric(array $rules, string $metric): float
    {
        if (empty($rules)) {
            return 0;
        }

        $sum = array_sum(array_column($rules, $metric));
        return round($sum / count($rules), 4);
    }

    /**
     * Xóa cache
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
        $this->analysis = null;
    }
}
