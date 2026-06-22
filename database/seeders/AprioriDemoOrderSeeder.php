<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class AprioriDemoOrderSeeder extends Seeder
{
    public function run()
    {
        Order::query()
            ->where('code', 'like', 'APRDEMO%')
            ->get()
            ->each(function (Order $order) {
                $order->items()->delete();
                $order->delete();
            });

        $products = Product::query()
            ->whereIn('slug', $this->demoSlugs())
            ->get()
            ->keyBy('slug');

        $orders = [
            ['nho-ngon-tay-my', 'cherry-noi-dia-trung', 'tao-juliet'],
            ['nho-ngon-tay-my', 'cherry-noi-dia-trung', 'viet-quat-newzealand'],
            ['nho-ngon-tay-my', 'tao-juliet', 'nho-xanh-uc-btm-sweetglobe'],
            ['nho-ngon-tay-my', 'cherry-noi-dia-trung', 'hop-qua-pastel-pt1200'],
            ['nho-ngon-tay-my', 'tao-juliet', 'viet-quat-newzealand'],
            ['cherry-noi-dia-trung', 'tao-juliet'],
            ['gio-qua-trai-cay-chuc-mung-sinh-nhat-vc1500', 'hop-qua-pastel-pt1200', 'cherry-noi-dia-trung'],
            ['nho-xanh-uc-btm-sweetglobe', 'tao-juliet', 'viet-quat-newzealand'],
        ];

        foreach ($orders as $index => $slugs) {
            $items = collect($slugs)
                ->map(function (string $slug) use ($products) {
                    return $products->get($slug);
                })
                ->filter()
                ->values();

            if ($items->count() < 2) {
                continue;
            }

            $subtotal = (int) $items->sum(function (Product $product) {
                return $this->unitPrice($product);
            });

            $order = Order::query()->create([
                'code' => 'APRDEMO' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'user_id' => null,
                'customer_name' => 'Khách demo Apriori',
                'customer_phone' => '0333499426',
                'customer_email' => null,
                'shipping_address' => 'Dữ liệu mẫu phục vụ báo cáo thuật toán Apriori',
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'discount_total' => 0,
                'total' => $subtotal,
                'coupon_code' => null,
                'status' => Order::STATUS_CONFIRMED,
            ]);

            foreach ($items as $product) {
                $unitPrice = $this->unitPrice($product);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'unit_price' => $unitPrice,
                    'qty' => 1,
                    'line_total' => $unitPrice,
                ]);
            }
        }
    }

    private function demoSlugs(): array
    {
        return [
            'nho-ngon-tay-my',
            'cherry-noi-dia-trung',
            'tao-juliet',
            'viet-quat-newzealand',
            'nho-xanh-uc-btm-sweetglobe',
            'hop-qua-pastel-pt1200',
            'gio-qua-trai-cay-chuc-mung-sinh-nhat-vc1500',
        ];
    }

    private function unitPrice(Product $product): int
    {
        $basePrice = (int) ($product->price ?? 0);
        $salePrice = (int) ($product->sale_price ?? 0);

        if ($salePrice > 0 && $basePrice > 0 && $salePrice < $basePrice) {
            return $salePrice;
        }

        return $basePrice;
    }
}
