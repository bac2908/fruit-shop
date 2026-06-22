<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Support\AprioriHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DebugAprioriCommand extends Command
{
    protected $signature = 'apriori:debug
                            {product_id? : Product ID to debug}
                            {--clear-cache : Clear Apriori cache}';

    protected $description = 'Debug Apriori recommendation system';

    public function handle(): int
    {
        if ($this->option('clear-cache')) {
            Cache::flush();
            $this->info('✅ Cache cleared!');
            return self::SUCCESS;
        }

        $this->line('📊 === APRIORI DEBUG ===');

        // 1. Kiểm tra dữ liệu đơn hàng
        $ordersCount = Order::count();
        $validOrdersCount = Order::where('status', '!=', Order::STATUS_CANCELLED)->count();
        $this->line("\n📦 Orders:");
        $this->line("  Total: $ordersCount");
        $this->line("  Valid: $validOrdersCount");

        // 2. Kiểm tra dữ liệu order items
        $ordersWithItems = Order::where('status', '!=', Order::STATUS_CANCELLED)
            ->whereHas('items')
            ->count();
        $ordersWithMultipleItems = Order::where('status', '!=', Order::STATUS_CANCELLED)
            ->whereIn('id', function ($q) {
                $q->from('order_items')
                    ->select('order_id')
                    ->groupBy('order_id')
                    ->havingRaw('COUNT(*) >= 2');
            })
            ->count();
        $this->line("\n📋 Order Items:");
        $this->line("  Orders with items: $ordersWithItems");
        $this->line("  Orders with 2+ items: $ordersWithMultipleItems");

        // 3. Kiểm tra từ service
        $this->line("\n🔍 Apriori Analysis:");
        $stats = AprioriHelper::service()->getStats();
        $this->line("  Transaction count: {$stats['transaction_count']}");
        $this->line("  Unique products: {$stats['unique_products']}");
        $this->line("  2-Itemsets found: {$stats['total_unique_itemsets_2items']}");
        $this->line("  Rules 2-items: {$stats['rules_count_2items']}");
        $this->line("  Total rules: {$stats['total_rules_count']}");

        // 4. Kiểm tra config
        $this->line("\n⚙️ Configuration:");
        $this->line("  Min support: {$stats['min_support']}");
        $this->line("  Min confidence: {$stats['min_confidence']}");
        $this->line("  Min lift: {$stats['min_lift']}");
        $this->line("  Min pair count: {$stats['min_pair_count']}");

        // 5. Nếu có product_id, kiểm tra gợi ý cho sản phẩm cụ thể
        if ($this->argument('product_id')) {
            $productId = (int) $this->argument('product_id');
            $product = Product::find($productId);

            if (!$product) {
                $this->error("❌ Product {$productId} not found!");
                return self::FAILURE;
            }

            $this->line("\n🎯 Recommendations for Product {$product->id} ({$product->name}):");
            $recs = AprioriHelper::service()->recommendForProduct($product, 10);

            if ($recs->isEmpty()) {
                $this->warn("  No recommendations found");
            } else {
                foreach ($recs as $rec) {
                    $recProd = $rec['product'];
                    $sup = AprioriHelper::formatSupport($rec['support']);
                    $conf = AprioriHelper::formatConfidence($rec['confidence']);
                    $lift = AprioriHelper::formatLift($rec['lift']);
                    $this->line("  → {$recProd->name} (Support: $sup, Confidence: $conf, Lift: $lift)");
                }
            }
        }

        return self::SUCCESS;
    }
}
