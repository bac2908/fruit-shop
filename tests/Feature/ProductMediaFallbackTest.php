<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductMediaFallbackTest extends TestCase
{
    use DatabaseTransactions;

    public function test_product_detail_and_quick_view_expose_a_local_fallback_image(): void
    {
        $category = Category::query()->create([
            'name' => 'Danh mục ảnh kiểm thử',
            'slug' => 'media-fallback-category',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm ảnh dự phòng',
            'slug' => 'media-fallback-product',
            'sku' => 'MEDIA-FALLBACK-TEST',
            'unit' => 'kg',
            'stock' => 10,
            'price' => 100000,
            'thumb' => 'images/placeholder-product.svg',
            'short_desc' => 'Sản phẩm kiểm thử cơ chế ảnh dự phòng cục bộ.',
            'description' => 'Sản phẩm kiểm thử cơ chế ảnh dự phòng cục bộ khi dịch vụ lưu trữ ảnh bên ngoài không phản hồi.',
            'is_active' => true,
        ]);
        $product->images()->createMany([
            ['url' => 'images/placeholder-product.svg', 'sort_order' => 0],
            ['url' => 'https://cdn.example.test/unavailable.jpg', 'sort_order' => 1],
        ]);

        $localImage = asset('images/placeholder-product.svg');

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('data-fallback-image="'.e($localImage).'"', false);

        $this->getJson(route('products.quick-view', $product->slug))
            ->assertOk()
            ->assertJsonPath('fallback_image', $localImage);
    }
}
