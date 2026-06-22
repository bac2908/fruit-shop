<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HomeBannerProductSeeder extends Seeder
{
    public function run()
    {
        $mangCutCategory = Category::query()->where('slug', 'mang-cut')->first();
        $vaiCategory = Category::query()->where('slug', 'trai-vai')->first();

        $products = [
            [
                'category_id' => optional($mangCutCategory)->id,
                'name' => 'Măng cụt Lái Thiêu - 1kg',
                'slug' => 'mang-cut-lai-thieu',
                'unit' => 'kg',
                'stock' => 100,
                'price' => 120000,
                'sale_price' => null,
                'thumb' => 'https://product.hstatic.net/200000157781/product/mang_cut_640fe24e8a844043b04be67ff9033b10.png',
                'short_desc' => 'Măng cụt Lái Thiêu vào mùa, vị ngọt đậm, thịt trắng mịn.',
                'description' => '<p>Măng cụt Lái Thiêu tuyển chọn theo mùa, trái tươi, vỏ đẹp, phù hợp ăn tươi hoặc làm quà biếu.</p>',
                'is_active' => true,
                'has_gear_detail' => false,
                'images' => [
                    'https://product.hstatic.net/200000157781/product/mang_cut_640fe24e8a844043b04be67ff9033b10.png',
                    'https://product.hstatic.net/200000157781/product/mang_cut_bao_loc__1__e28ea5fc084a468b805449fe05085506.jpg',
                    'https://product.hstatic.net/200000157781/product/mang_cut_bao_loc__3__bb9467240f414fcfbe31798610569df1.jpg',
                    'https://product.hstatic.net/200000157781/product/mang_cut_bao_loc__4__c1b0ff5e1b81467c85458a8b96c2cd2a.jpg',
                    'https://product.hstatic.net/200000157781/product/mang_cut_bao_loc__5__fd6c39588a8847a4ba633a1a64b90246.jpg',
                ],
            ],
            [
                'category_id' => optional($vaiCategory)->id,
                'name' => 'Vải Thiều hàng máy bay - hộp 500gr',
                'slug' => 'vai-thieu-luc-ngan',
                'unit' => 'hộp 500gr',
                'stock' => 100,
                'price' => 60000,
                'sale_price' => null,
                'thumb' => 'https://product.hstatic.net/200000157781/product/vai_thieu_3fb35aff5f58497ca88251a285c53b1b.png',
                'short_desc' => 'Vải thiều hàng máy bay, thịt dày, ngọt đậm, vỏ đỏ tươi.',
                'description' => '<p>Vải thiều hàng máy bay được tuyển chọn kỹ, giao nhanh để giữ độ tươi, phù hợp dùng trong ngày hoặc làm quà.</p>',
                'is_active' => true,
                'has_gear_detail' => false,
                'images' => [
                    'https://product.hstatic.net/200000157781/product/vai_thieu_3fb35aff5f58497ca88251a285c53b1b.png',
                    'https://product.hstatic.net/200000157781/product/vai_thieu_luc_ngan__2__bb382f19a1904ffeac8c817154ff7eab.jpg',
                    'https://product.hstatic.net/200000157781/product/vai_thieu_luc_ngan__5__be434fc0dcb441eab76dcce8906d7c71.jpg',
                    'https://product.hstatic.net/200000157781/product/vai_thieu_luc_ngan__6__f0bf12511d804d3ba28504c4570a522d.jpg',
                    'https://product.hstatic.net/200000157781/product/vai_thieu_luc_ngan__4__0d7915d718ff4ee9ad34989a1e404c38.jpg',
                    'https://product.hstatic.net/200000157781/product/vai_thieu_luc_ngan__1__96cec37402684aa293e7b6bedaa80d3b.jpg',
                ],
            ],
        ];

        foreach ($products as $productData) {
            $imageUrls = $productData['images'];
            unset($productData['images']);

            $product = Product::query()->updateOrCreate(
                ['slug' => $productData['slug']],
                array_merge($productData, [
                    'sku' => Str::upper(str_replace('-', '_', $productData['slug'])),
                    'low_stock_threshold' => 10,
                    'sort_order' => 0,
                ])
            );

            foreach ($imageUrls as $sortOrder => $imageUrl) {
                ProductImage::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sort_order' => $sortOrder,
                    ],
                    [
                        'url' => $imageUrl,
                    ]
                );
            }
        }

        if ($this->command) {
            $this->command->info('Home banner products seeded/refreshed: ' . count($products));
        }
    }
}
