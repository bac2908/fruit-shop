<?php

namespace App\Console\Commands;

use App\Support\AprioriHelper;
use App\Support\LocalDateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateAprioriReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apriori:report
                            {--format=html : Output format (html, csv, json)}
                            {--output= : Output file path}
                            {--open : Open file in browser after generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Apriori analysis report in multiple formats';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 Generating Apriori Report...');

        try {
            $format = $this->option('format') ?? 'html';
            $output = $this->option('output');

            switch ($format) {
                case 'html':
                    $this->generateHtmlReport($output);
                    break;
                case 'csv':
                    $this->generateCsvReport($output);
                    break;
                case 'json':
                    $this->generateJsonReport($output);
                    break;
                default:
                    throw new \InvalidArgumentException("Format '{$format}' not supported");
            }

            $this->info("✅ Report generated successfully!");

            if ($this->option('open') && $output && file_exists($output)) {
                $this->openFile($output);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function generateHtmlReport(?string $outputPath): void
    {
        $stats = AprioriHelper::service()->getStats();
        $rules = AprioriHelper::service()->getAllRules(null, null, null, 50);
        $itemsets2 = AprioriHelper::service()->getFrequentItemsets(2);
        $itemsets3 = AprioriHelper::service()->getFrequentItemsets(3);

        $html = $this->buildHtmlTemplate($stats, $rules, $itemsets2, $itemsets3);

        $filepath = $outputPath ?? storage_path('app/apriori_report_' . LocalDateTime::format(now(), 'YmdHis') . '.html');
        file_put_contents($filepath, $html);

        $this->info("📄 HTML Report: <fg=green>$filepath</>");
    }

    private function generateCsvReport(?string $outputPath): void
    {
        $rulesPath = $outputPath ?? storage_path('app/apriori_rules_' . LocalDateTime::format(now(), 'YmdHis') . '.csv');
        $itemsetsPath = storage_path('app/apriori_itemsets_' . LocalDateTime::format(now(), 'YmdHis') . '.csv');

        AprioriHelper::exportRulesToCsv($rulesPath);
        AprioriHelper::exportItemsetsToCsv($itemsetsPath, 2);

        $this->info("📄 CSV Reports:");
        $this->line("  Rules: <fg=green>$rulesPath</>");
        $this->line("  Itemsets: <fg=green>$itemsetsPath</>");
    }

    private function generateJsonReport(?string $outputPath): void
    {
        $stats = AprioriHelper::service()->getStats();
        $rules = AprioriHelper::service()->getAllRules(null, null, null, 100)->toArray();
        $itemsets = [
            '2_itemsets' => AprioriHelper::service()->getFrequentItemsets(2)->toArray(),
            '3_itemsets' => AprioriHelper::service()->getFrequentItemsets(3)->toArray(),
        ];

        $data = [
            'generated_at' => now()->toIso8601String(),
            'stats' => $stats,
            'rules' => $rules,
            'itemsets' => $itemsets,
        ];

        $filepath = $outputPath ?? storage_path('app/apriori_report_' . LocalDateTime::format(now(), 'YmdHis') . '.json');
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("📄 JSON Report: <fg=green>$filepath</>");
    }

    private function buildHtmlTemplate(array $stats, $rules, $itemsets2, $itemsets3): string
    {
        $generatedAt = LocalDateTime::format(now(), 'd/m/Y H:i:s');

        $rulesTable = '';
        foreach ($rules->take(20) as $rule) {
            $antId = $rule['antecedent_id'];
            $consId = $rule['consequent_id'];
            $sup = AprioriHelper::formatSupport($rule['support']);
            $conf = AprioriHelper::formatConfidence($rule['confidence']);
            $lift = AprioriHelper::formatLift($rule['lift']);
            $strength = AprioriHelper::assessRuleStrength($rule);
            $strengthClass = $this->getStrengthClass($strength);

            $rulesTable .= <<<HTML
                <tr>
                    <td>$antId</td>
                    <td>→</td>
                    <td>$consId</td>
                    <td>$sup</td>
                    <td>$conf</td>
                    <td>$lift</td>
                    <td><span class="badge badge-{$strengthClass}">$strength</span></td>
                </tr>
HTML;
        }

        $itemsets2Table = '';
        foreach ($itemsets2->take(15) as $itemset) {
            $items = implode(', ', $itemset['items']);
            $sup = AprioriHelper::formatSupport($itemset['support']);
            $itemsets2Table .= <<<HTML
                <tr>
                    <td>[$items]</td>
                    <td>{$itemset['count']}</td>
                    <td>$sup</td>
                </tr>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Apriori Recommendation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card .label {
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table th {
            background-color: #f0f0f0;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }

        table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-very-strong {
            background-color: #28a745;
            color: white;
        }

        .badge-strong {
            background-color: #007bff;
            color: white;
        }

        .badge-medium {
            background-color: #ffc107;
            color: black;
        }

        .badge-weak {
            background-color: #fd7e14;
            color: white;
        }

        .badge-very-weak {
            background-color: #dc3545;
            color: white;
        }

        .metric-box {
            display: inline-block;
            margin: 10px 20px 10px 0;
            padding: 15px 20px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }

        .metric-box strong {
            color: #667eea;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9em;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                background: white;
            }
            .section {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Báo Cáo Apriori Recommendation</h1>
            <p>Phân tích Quy Tắc Kết Hợp Sản Phẩm</p>
            <p style="font-size: 0.9em; margin-top: 10px;">Tạo lúc: $generatedAt</p>
        </header>

        <!-- Statistics Section -->
        <div class="section">
            <h2>📈 Thống Kê Giao Dịch</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Tổng Đơn Hàng</div>
                    <div class="value">{$stats['orders_count']}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Giao Dịch Hợp Lệ</div>
                    <div class="value">{$stats['transaction_count']}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Sản Phẩm Duy Nhất</div>
                    <div class="value">{$stats['unique_products']}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Bình Quân SP/Giao Dịch</div>
                    <div class="value">{$stats['avg_items_per_transaction']}</div>
                </div>
            </div>
        </div>

        <!-- Itemsets Section -->
        <div class="section">
            <h2>🎯 Frequent Itemsets Phát Hiện</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">2-Itemsets</div>
                    <div class="value">{$stats['total_unique_itemsets_2items']}</div>
                </div>
                <div class="stat-card">
                    <div class="label">3-Itemsets</div>
                    <div class="value">{$stats['total_unique_itemsets_3items']}</div>
                </div>
                <div class="stat-card">
                    <div class="label">4-Itemsets</div>
                    <div class="value">{$stats['total_unique_itemsets_4items']}</div>
                </div>
            </div>
        </div>

        <!-- Rules Section -->
        <div class="section">
            <h2>📋 Association Rules</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Quy Tắc 2-Items</div>
                    <div class="value">{$stats['rules_count_2items']}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Quy Tắc 3-Items</div>
                    <div class="value">{$stats['rules_count_3items']}</div>
                </div>
                <div class="stat-card">
                    <div class="label">Tổng Quy Tắc</div>
                    <div class="value">{$stats['total_rules_count']}</div>
                </div>
            </div>

            <h3 style="margin-top: 30px; margin-bottom: 15px;">🏆 Quy Tắc Mạnh Nhất (Top 20)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Antecedent</th>
                        <th></th>
                        <th>Consequent</th>
                        <th>Support</th>
                        <th>Confidence</th>
                        <th>Lift</th>
                        <th>Mức Độ</th>
                    </tr>
                </thead>
                <tbody>
                    $rulesTable
                </tbody>
            </table>
        </div>

        <!-- Itemsets Details -->
        <div class="section">
            <h2>🔗 Chi Tiết 2-Itemsets (Top 15)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Itemset</th>
                        <th>Xuất Hiện</th>
                        <th>Support</th>
                    </tr>
                </thead>
                <tbody>
                    $itemsets2Table
                </tbody>
            </table>
        </div>

        <!-- Metrics -->
        <div class="section">
            <h2>📊 Chỉ Số Trung Bình</h2>
            <div class="metric-box">
                <strong>Avg Support:</strong> {$stats['avg_support']}
            </div>
            <div class="metric-box">
                <strong>Avg Confidence:</strong> {$stats['avg_confidence']}
            </div>
            <div class="metric-box">
                <strong>Avg Lift:</strong> {$stats['avg_lift']}
            </div>
        </div>

        <!-- Configuration -->
        <div class="section">
            <h2>⚙️ Cấu Hình Apriori</h2>
            <div class="metric-box">
                <strong>Min Support:</strong> {$stats['min_support']}
            </div>
            <div class="metric-box">
                <strong>Min Confidence:</strong> {$stats['min_confidence']}
            </div>
            <div class="metric-box">
                <strong>Min Lift:</strong> {$stats['min_lift']}
            </div>
        </div>

        <footer>
            <p>© 2026 Fruit Shop. Báo cáo được tạo tự động bởi Apriori Recommendation Engine</p>
        </footer>
    </div>
</body>
</html>
HTML;
    }

    private function openFile(string $filepath): void
    {
        $os = PHP_OS_FAMILY;

        if ($os === 'Windows') {
            exec("start $filepath");
        } elseif ($os === 'Darwin') {
            exec("open $filepath");
        } elseif ($os === 'Linux') {
            exec("xdg-open $filepath");
        }

        $this->info("🌐 Opening file in browser...");
    }

    private function getStrengthClass(string $strength): string
    {
        switch ($strength) {
            case 'Rất mạnh':
                return 'very-strong';
            case 'Mạnh':
                return 'strong';
            case 'Trung bình':
                return 'medium';
            case 'Yếu':
                return 'weak';
            case 'Rất yếu':
                return 'very-weak';
            default:
                return 'medium';
        }
    }
}
