<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\AprioriRecommendationService;
use App\Services\ProductRecommendationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class ProductRecommendationServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_catalog_fallback_only_returns_products_that_can_be_added_directly(): void
    {
        $category = $this->createCategory();
        $currentProduct = $this->createProduct($category, 'Sản phẩm đang xem');
        $this->createProduct($category, 'Sản phẩm gợi ý hợp lệ');
        $this->createProduct($category, 'Sản phẩm hết hàng', ['stock' => 0]);
        $this->createProduct($category, 'Giỏ quà đặt riêng', ['price' => 1500000]);

        $apriori = Mockery::mock(AprioriRecommendationService::class);
        $apriori->shouldReceive('recommendForProduct')->once()->andReturn(collect());

        $result = (new ProductRecommendationService($apriori))->recommend($currentProduct, 3);

        $this->assertSame('catalog', $result['source']);
        $this->assertSame('Gợi ý thêm cho giỏ hàng', $result['title']);
        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $recommendation) {
            $product = $recommendation['product'];
            $this->assertNotSame($currentProduct->id, $product->id);
            $this->assertTrue((bool) $product->is_active);
            $this->assertGreaterThan(0, (int) $product->stock);
            $this->assertGreaterThan(0, (int) $product->orderable_price);
            $this->assertFalse((bool) $product->has_gear_detail);
            $this->assertFalse((bool) $product->is_custom_order_product);
        }
    }

    public function test_non_orderable_current_product_does_not_render_an_empty_recommendation_box(): void
    {
        $category = $this->createCategory();
        $currentProduct = $this->createProduct($category, 'Sản phẩm tạm hết hàng', ['stock' => 0]);

        $apriori = Mockery::mock(AprioriRecommendationService::class);
        $apriori->shouldNotReceive('recommendForProduct');

        $result = (new ProductRecommendationService($apriori))->recommend($currentProduct, 3);

        $this->assertSame('none', $result['source']);
        $this->assertTrue($result['items']->isEmpty());
    }

    public function test_product_page_renders_the_selectable_bundle_without_exposing_technical_empty_state(): void
    {
        $category = $this->createCategory();
        $currentProduct = $this->createProduct($category, 'Sản phẩm trang chi tiết');
        $recommendedProduct = $this->createProduct($category, 'Sản phẩm chọn mua kèm');
        $recommendedProduct->load('category');

        $recommendationService = Mockery::mock(ProductRecommendationService::class);
        $recommendationService
            ->shouldReceive('recommend')
            ->once()
            ->with(Mockery::on(fn (Product $product) => $product->is($currentProduct)), 3)
            ->andReturn([
                'items' => collect([[
                    'product' => $recommendedProduct,
                    'source' => 'catalog',
                ]]),
                'source' => 'catalog',
                'title' => 'Gợi ý thêm cho giỏ hàng',
                'subtitle' => 'Những lựa chọn đang còn hàng.',
            ]);
        $this->app->instance(ProductRecommendationService::class, $recommendationService);

        $response = $this->get(route('products.show', $currentProduct->slug));

        $response
            ->assertOk()
            ->assertSee('data-bundle-form', false)
            ->assertSee('data-bundle-checkbox', false)
            ->assertSee('Gợi ý thêm cho giỏ hàng')
            ->assertSee($recommendedProduct->name)
            ->assertDontSee('Apriori recommendation')
            ->assertDontSee('Chưa có gợi ý mua kèm cho sản phẩm này');
    }

    private function createCategory(): Category
    {
        $suffix = uniqid();

        return Category::query()->create([
            'name' => 'Danh mục gợi ý '.$suffix,
            'slug' => 'recommendation-category-'.$suffix,
            'sort_order' => 999,
            'is_active' => true,
        ]);
    }

    private function createProduct(Category $category, string $name, array $overrides = []): Product
    {
        $suffix = uniqid();

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => $name.' '.$suffix,
            'slug' => 'recommendation-product-'.$suffix,
            'sku' => 'RECOMMEND-'.$suffix,
            'unit' => 'kg',
            'stock' => 10,
            'price' => 120000,
            'thumb' => 'images/test-product.jpg',
            'short_desc' => 'Sản phẩm dùng để kiểm thử gợi ý.',
            'description' => 'Sản phẩm dùng để kiểm thử gợi ý.',
            'is_active' => true,
            'has_gear_detail' => false,
            'sort_order' => 999,
        ], $overrides));
    }
}
