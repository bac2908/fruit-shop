<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\HomeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HomeCatalogSectionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_home_section_never_fills_missing_slots_from_another_category(): void
    {
        $suffix = uniqid();
        $category = Category::query()->create([
            'name' => 'Danh mục kiểm thử',
            'slug' => "home-section-{$suffix}",
            'sort_order' => 999,
            'is_active' => true,
        ]);
        $otherCategory = Category::query()->create([
            'name' => 'Danh mục khác',
            'slug' => "home-section-other-{$suffix}",
            'sort_order' => 1000,
            'is_active' => true,
        ]);

        $expected = $this->createProduct($category, "Sản phẩm đúng {$suffix}");
        $unexpected = $this->createProduct($otherCategory, "Sản phẩm sai {$suffix}");

        $section = app(HomeService::class)
            ->getHomeSections([$category->slug], 8)
            ->firstWhere('category.id', $category->id);

        $this->assertNotNull($section);
        $this->assertSame([$expected->id], $section['products']->pluck('id')->all());
        $this->assertFalse($section['products']->contains('id', $unexpected->id));
    }

    private function createProduct(Category $category, string $name): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug() . '-' . uniqid(),
            'sku' => 'TEST-' . uniqid(),
            'unit' => 'kg',
            'stock' => 5,
            'price' => 100000,
            'thumb' => 'images/test-product.jpg',
            'short_desc' => 'Dữ liệu dùng cho kiểm thử trang chủ.',
            'description' => 'Dữ liệu dùng cho kiểm thử trang chủ.',
            'is_active' => true,
        ]);
    }
}
