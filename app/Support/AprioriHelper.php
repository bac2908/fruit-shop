<?php

namespace App\Support;

use App\Services\AprioriRecommendationService;
use Illuminate\Support\Collection;

/**
 * Helper class để làm việc với Apriori Recommendation Service
 */
class AprioriHelper
{
    private static ?AprioriRecommendationService $service = null;

    /**
     * Lấy instance của service
     */
    public static function service(
        ?float $minSupport = null,
        ?float $minConfidence = null,
        ?float $minLift = null,
        ?int $minPairCount = null,
        ?int $maxItemsetSize = null
    ): AprioriRecommendationService {
        if (self::$service === null) {
            $minSupport = $minSupport ?? config('apriori.min_support', 0.02);
            $minConfidence = $minConfidence ?? config('apriori.min_confidence', 0.30);
            $minLift = $minLift ?? config('apriori.min_lift', 1.0);
            $minPairCount = $minPairCount ?? config('apriori.min_pair_count', 2);
            $maxItemsetSize = $maxItemsetSize ?? config('apriori.max_itemset_size', 4);

            self::$service = new AprioriRecommendationService(
                $minSupport,
                $minConfidence,
                $minLift,
                $minPairCount,
                $maxItemsetSize
            );
        }

        return self::$service;
    }

    /**
     * Lấy service với preset
     */
    public static function withPreset(string $preset = 'balanced'): AprioriRecommendationService
    {
        $config = config('apriori.presets.' . $preset);
        if (!$config) {
            throw new \InvalidArgumentException("Preset '{$preset}' không tìm thấy");
        }

        return new AprioriRecommendationService(
            $config['min_support'],
            $config['min_confidence'],
            $config['min_lift'],
            $config['min_pair_count'],
            $config['max_itemset_size']
        );
    }

    /**
     * Format Support thành phần trăm
     */
    public static function formatSupport(float $support): string
    {
        return round($support * 100, 2) . '%';
    }

    /**
     * Format Confidence thành phần trăm
     */
    public static function formatConfidence(float $confidence): string
    {
        return round($confidence * 100, 2) . '%';
    }

    /**
     * Format Lift với 2 chữ số thập phân
     */
    public static function formatLift(float $lift): string
    {
        return round($lift, 2) . 'x';
    }

    /**
     * Đánh giá độ mạnh của quy tắc
     */
    public static function assessRuleStrength(array $rule): string
    {
        $score = ($rule['lift'] * 3) + ($rule['confidence'] * 2) + $rule['support'];

        if ($score > 4) {
            return 'Rất mạnh';
        }

        if ($score > 3) {
            return 'Mạnh';
        }

        if ($score > 2) {
            return 'Trung bình';
        }

        if ($score > 1) {
            return 'Yếu';
        }

        return 'Rất yếu';
    }

    /**
     * Lấy gợi ý sản phẩm với chi tiết
     */
    public static function getRecommendationsWithDetails($product, int $limit = 4): Collection
    {
        $recommendations = self::service()->recommendForProduct($product, $limit);

        return $recommendations->map(function ($item) {
            return array_merge($item, [
                'support_display' => self::formatSupport($item['support']),
                'confidence_display' => self::formatConfidence($item['confidence']),
                'lift_display' => self::formatLift($item['lift']),
                'strength' => self::assessRuleStrength($item),
            ]);
        });
    }

    /**
     * Export Rules thành CSV
     */
    public static function exportRulesToCsv(string $filepath, ?int $limit = null): bool
    {
        $rules = self::service()->getAllRules(null, null, null, $limit ?? 1000);

        $csv = "antecedent_id,consequent_id,support,confidence,lift,support_pct,confidence_pct,pair_count\n";

        foreach ($rules as $rule) {
            $csv .= sprintf(
                "%s,%s,%.4f,%.4f,%.4f,%.2f%%,%.2f%%,%d\n",
                (string) $rule['antecedent_id'],
                (string) $rule['consequent_id'],
                $rule['support'],
                $rule['confidence'],
                $rule['lift'],
                $rule['support'] * 100,
                $rule['confidence'] * 100,
                $rule['pair_count']
            );
        }

        return file_put_contents($filepath, $csv) !== false;
    }

    /**
     * Export Itemsets thành CSV
     */
    public static function exportItemsetsToCsv(string $filepath, int $size = 2): bool
    {
        $itemsets = self::service()->getFrequentItemsets($size);

        $csv = "items,count,support,support_pct\n";

        foreach ($itemsets as $itemset) {
            $items = implode('-', $itemset['items']);
            $csv .= sprintf(
                "\"%s\",%d,%.4f,%.2f%%\n",
                $items,
                $itemset['count'],
                $itemset['support'],
                $itemset['support'] * 100
            );
        }

        return file_put_contents($filepath, $csv) !== false;
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        self::service()->clearCache();
        self::$service = null;
    }

    /**
     * Tạo báo cáo HTML
     */
    public static function generateHtmlReport(): string
    {
        $stats = self::service()->getStats();
        $topRules = $stats['strong_rules'];

        $html = <<<HTML
<div class="apriori-report">
    <h2>📊 Báo Cáo Apriori Recommendation</h2>

    <div class="section">
        <h3>📈 Thống Kê Giao Dịch</h3>
        <ul>
            <li><strong>Tổng đơn hàng:</strong> {$stats['orders_count']}</li>
            <li><strong>Giao dịch hợp lệ:</strong> {$stats['transaction_count']}</li>
            <li><strong>Bình quân sản phẩm/giao dịch:</strong> {$stats['avg_items_per_transaction']}</li>
            <li><strong>Sản phẩm duy nhất:</strong> {$stats['unique_products']}</li>
        </ul>
    </div>

    <div class="section">
        <h3>🎯 Itemsets Phát Hiện</h3>
        <ul>
            <li><strong>2-Itemsets:</strong> {$stats['total_unique_itemsets_2items']}</li>
            <li><strong>3-Itemsets:</strong> {$stats['total_unique_itemsets_3items']}</li>
            <li><strong>4-Itemsets:</strong> {$stats['total_unique_itemsets_4items']}</li>
        </ul>
    </div>

    <div class="section">
        <h3>📋 Quy Tắc Kết Hợp</h3>
        <ul>
            <li><strong>Quy tắc 2-items:</strong> {$stats['rules_count_2items']}</li>
            <li><strong>Quy tắc 3-items:</strong> {$stats['rules_count_3items']}</li>
            <li><strong>Quy tắc 4-items:</strong> {$stats['rules_count_4items']}</li>
            <li><strong>Tổng cộng:</strong> {$stats['total_rules_count']}</li>
        </ul>
    </div>

    <div class="section">
        <h3>📊 Chỉ Số Trung Bình</h3>
        <ul>
            <li><strong>Support:</strong> {$stats['avg_support']}</li>
            <li><strong>Confidence:</strong> {$stats['avg_confidence']}</li>
            <li><strong>Lift:</strong> {$stats['avg_lift']}</li>
        </ul>
    </div>

    <div class="section">
        <h3>🏆 Quy Tắc Mạnh Nhất</h3>
        <table>
            <thead>
                <tr>
                    <th>Antecedent</th>
                    <th>Consequent</th>
                    <th>Support</th>
                    <th>Confidence</th>
                    <th>Lift</th>
                </tr>
            </thead>
            <tbody>
HTML;

        foreach ($topRules as $rule) {
            $support = self::formatSupport($rule['support']);
            $confidence = self::formatConfidence($rule['confidence']);
            $lift = self::formatLift($rule['lift']);

            $html .= <<<HTML
                <tr>
                    <td>{$rule['antecedent_id']}</td>
                    <td>{$rule['consequent_id']}</td>
                    <td>{$support}</td>
                    <td>{$confidence}</td>
                    <td>{$lift}</td>
                </tr>
HTML;
        }

        $html .= <<<HTML
            </tbody>
        </table>
    </div>

    <style>
        .apriori-report {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .section h3 {
            color: #333;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
    </style>
</div>
HTML;

        return $html;
    }
}
