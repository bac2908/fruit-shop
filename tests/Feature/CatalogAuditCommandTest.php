<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatalogAuditCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_safe_fix_generates_sku_maps_thumb_and_normalizes_metadata(): void
    {
        $category = $this->createCategory();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => '  Sản phẩm   kiểm thử  ',
            'slug' => 'catalog-audit-'.uniqid(),
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

    public function test_safe_fix_prefers_an_owned_optimized_primary_image(): void
    {
        $category = $this->createCategory();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm ảnh cục bộ',
            'slug' => 'catalog-local-media-'.uniqid(),
            'sku' => 'LOCAL-'.uniqid(),
            'unit' => 'kg',
            'stock' => 5,
            'price' => 100000,
            'thumb' => 'https://cdn.example.test/product.jpg',
            'short_desc' => 'Sản phẩm kiểm thử có mô tả ngắn đầy đủ cho catalog.',
            'description' => 'Sản phẩm kiểm thử có phần mô tả chi tiết dài hơn tám mươi ký tự để xác nhận quá trình kiểm tra dữ liệu catalog hoạt động ổn định.',
            'is_active' => true,
        ]);
        $product->images()->create([
            'url' => 'https://cdn.example.test/product.jpg',
            'sort_order' => 0,
        ]);

        $relativePath = "images/products_synced/{$product->id}-{$product->slug}.webp";
        File::ensureDirectoryExists(dirname(public_path($relativePath)));
        File::put(public_path($relativePath), 'test-webp');

        try {
            $this->artisan('catalog:audit', [
                '--product' => $product->id,
                '--fix-safe' => true,
            ])->assertSuccessful();

            $product->refresh();
            $this->assertSame($relativePath, $product->thumb);
            $this->assertSame($relativePath, $product->images()->orderBy('sort_order')->value('url'));
        } finally {
            File::delete(public_path($relativePath));
        }
    }

    public function test_safe_fix_finds_an_owned_image_when_the_historical_filename_slug_differs(): void
    {
        $category = $this->createCategory();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm đã đổi slug',
            'slug' => 'catalog-current-slug-'.uniqid(),
            'sku' => 'HISTORY-'.uniqid(),
            'unit' => 'hộp',
            'stock' => 5,
            'price' => 120000,
            'thumb' => 'https://cdn.example.test/history.jpg',
            'short_desc' => 'Sản phẩm kiểm thử đường dẫn ảnh lịch sử sau khi slug thay đổi.',
            'description' => 'Sản phẩm kiểm thử có phần mô tả chi tiết dài hơn tám mươi ký tự để xác nhận ảnh lịch sử vẫn được tìm thấy bằng mã sản phẩm.',
            'is_active' => true,
        ]);
        $product->images()->create([
            'url' => 'https://cdn.example.test/history.jpg',
            'sort_order' => 0,
        ]);

        $relativePath = "images/products_synced/{$product->id}-historical-slug.webp";
        File::ensureDirectoryExists(dirname(public_path($relativePath)));
        File::put(public_path($relativePath), 'test-webp');

        try {
            $this->artisan('catalog:audit', [
                '--product' => $product->id,
                '--fix-safe' => true,
            ])->assertSuccessful();

            $product->refresh();
            $this->assertSame($relativePath, $product->thumb);
            $this->assertSame($relativePath, $product->images()->orderBy('sort_order')->value('url'));
        } finally {
            File::delete(public_path($relativePath));
        }
    }

    public function test_safe_fix_infers_only_an_unambiguous_selling_unit(): void
    {
        $category = $this->createCategory();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mâm trái cây kiểm thử',
            'slug' => 'catalog-unit-'.uniqid(),
            'sku' => 'UNIT-'.uniqid(),
            'unit' => null,
            'stock' => 5,
            'price' => 650000,
            'thumb' => 'images/placeholder-product.svg',
            'short_desc' => 'Mâm trái cây kiểm thử có thông tin tóm tắt đầy đủ cho catalog.',
            'description' => 'Mâm trái cây kiểm thử có phần mô tả chi tiết dài hơn tám mươi ký tự để xác nhận quá trình suy luận đơn vị bán hoạt động ổn định.',
            'is_active' => true,
        ]);

        $this->artisan('catalog:audit', [
            '--product' => $product->id,
            '--fix-safe' => true,
        ])->assertSuccessful();

        $this->assertSame('mâm', $product->fresh()->unit);
    }

    private function createCategory(): Category
    {
        return Category::query()->create([
            'name' => 'Danh mục kiểm thử',
            'slug' => 'catalog-audit-category-'.uniqid(),
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }
}
