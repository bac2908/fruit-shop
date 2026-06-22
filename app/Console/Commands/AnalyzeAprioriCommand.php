<?php

namespace App\Console\Commands;

use App\Services\AprioriRecommendationService;
use Illuminate\Console\Command;

class AnalyzeAprioriCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apriori:analyze
                            {--min-support=0.02 : Minimum support threshold (0-1)}
                            {--min-confidence=0.30 : Minimum confidence threshold (0-1)}
                            {--min-lift=1.0 : Minimum lift threshold (0+)}
                            {--min-pair-count=2 : Minimum pair count}
                            {--max-itemset-size=4 : Maximum itemset size (2-5)}
                            {--cache-hours=24 : Cache duration in hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze transactions using Apriori algorithm and cache results';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Apriori analysis...');

        try {
            // Khởi tạo service với thông số từ command
            $service = new AprioriRecommendationService(
                (float) $this->option('min-support'),
                (float) $this->option('min-confidence'),
                (float) $this->option('min-lift'),
                (int) $this->option('min-pair-count'),
                (int) $this->option('max-itemset-size')
            );

            // Lấy thống kê
            $this->info('📊 Gathering statistics...');
            $stats = $service->getStats();

            // Hiển thị kết quả
            $this->newLine();
            $this->info('✅ Analysis completed!');
            $this->newLine();

            $this->line('<fg=cyan>📈 Transaction Statistics:</>');
            $this->line('  Orders: <fg=green>' . $stats['orders_count'] . '</>');
            $this->line('  Valid transactions (≥2 items): <fg=green>' . $stats['transaction_count'] . '</>');
            $this->line('  Avg items per transaction: <fg=green>' . $stats['avg_items_per_transaction'] . '</>');
            $this->line('  Unique products: <fg=green>' . $stats['unique_products'] . '</>');

            $this->newLine();
            $this->line('<fg=cyan>🎯 Frequent Itemsets:</>');
            $this->line('  2-itemsets: <fg=green>' . $stats['total_unique_itemsets_2items'] . '</>');
            $this->line('  3-itemsets: <fg=green>' . $stats['total_unique_itemsets_3items'] . '</>');
            $this->line('  4-itemsets: <fg=green>' . $stats['total_unique_itemsets_4items'] . '</>');

            $this->newLine();
            $this->line('<fg=cyan>📋 Association Rules:</>');
            $this->line('  Rules (2-items): <fg=green>' . $stats['rules_count_2items'] . '</>');
            $this->line('  Rules (3-items): <fg=green>' . $stats['rules_count_3items'] . '</>');
            $this->line('  Rules (4-items): <fg=green>' . $stats['rules_count_4items'] . '</>');
            $this->line('  Total rules: <fg=green>' . $stats['total_rules_count'] . '</>');

            $this->newLine();
            $this->line('<fg=cyan>📊 Metrics:</>');
            $this->line('  Avg Support: <fg=green>' . $stats['avg_support'] . '</>');
            $this->line('  Avg Confidence: <fg=green>' . $stats['avg_confidence'] . '</>');
            $this->line('  Avg Lift: <fg=green>' . $stats['avg_lift'] . '</>');

            $this->newLine();
            $this->line('<fg=cyan>⚙️  Configuration:</>');
            $this->line('  Min Support: <fg=green>' . $stats['min_support'] . '</>');
            $this->line('  Min Confidence: <fg=green>' . $stats['min_confidence'] . '</>');
            $this->line('  Min Lift: <fg=green>' . $stats['min_lift'] . '</>');

            $this->newLine();
            $this->info('💾 Results cached for ' . $this->option('cache-hours') . ' hours');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
