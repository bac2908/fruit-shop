<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatalogAuditCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_safe_fix_generates_sku_maps_thumb_and_normalizes_metadata(): void
    {
        $category = Category::query()->firstOrFail();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => '  Sản phẩm   kiểm thử  ',
            'slug' => 'catalog-audit-' . uniqid(),
            'sku' => null,
            'unit' => 'kg',
            'stock' => 5,
            'price' => 100000,
            'sale_price' => 120000,
            'thumb' => 'images/images_traicayvn/vu_sua_tim_71a6600dbb5d44bd9ca5c286a62dc6ef_medium.jpg',
            'short_desc' => 'Trái cây tươi dùng để kiểm thử catalog.',
            'description' => 'Trái cây tươi dùng để kiểm thử catalog.',
            'is_active' => true,
        ]);

        $this->artisan('catalog:audit', [
            '--product' => $product->id,
            '--fix-safe' => true,
        ])->assertSuccessful();

        $product->refresh();
        $this->assertNotEmpty($product->sku);
        $this->assertNull($product->sale_price);
        $this->assertSame('Sản phẩm kiểm thử', $product->name);
        $this->assertNotEmpty($product->meta_title);
        $this->assertNotEmpty($product->meta_description);
        $this->assertSame(1, $product->images()->count());
    }
}
