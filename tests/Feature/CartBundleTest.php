<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CartBundleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_selected_bundle_products_are_added_to_the_cart_together(): void
    {
        $category = $this->createCategory();
        $firstProduct = $this->createProduct($category, 'Sản phẩm chính');
        $secondProduct = $this->createProduct($category, 'Sản phẩm mua kèm');

        $response = $this->post(route('cart.bundle.add'), [
            'product_ids' => [$firstProduct->id, $secondProduct->id],
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã thêm 2 sản phẩm đã chọn vào giỏ hàng.');
        $response->assertSessionHas('cart.'.$firstProduct->id.'.quantity', 1);
        $response->assertSessionHas('cart.'.$secondProduct->id.'.quantity', 1);
    }

    public function test_bundle_is_not_partially_added_when_one_product_is_unavailable(): void
    {
        $category = $this->createCategory();
        $availableProduct = $this->createProduct($category, 'Sản phẩm còn hàng');
        $unavailableProduct = $this->createProduct($category, 'Sản phẩm hết hàng', 0);

        $response = $this->post(route('cart.bundle.add'), [
            'product_ids' => [$availableProduct->id, $unavailableProduct->id],
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame([], session('cart', []));
    }

    private function createCategory(): Category
    {
        $suffix = uniqid();

        return Category::query()->create([
            'name' => 'Danh mục bundle '.$suffix,
            'slug' => 'bundle-category-'.$suffix,
            'sort_order' => 999,
            'is_active' => true,
        ]);
    }

    private function createProduct(Category $category, string $name, int $stock = 10): Product
    {
        $suffix = uniqid();

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => $name.' '.$suffix,
            'slug' => 'bundle-product-'.$suffix,
            'sku' => 'BUNDLE-'.$suffix,
            'unit' => 'kg',
            'stock' => $stock,
            'price' => 120000,
            'thumb' => 'images/test-product.jpg',
            'short_desc' => 'Sản phẩm dùng để kiểm thử mua kèm.',
            'description' => 'Sản phẩm dùng để kiểm thử mua kèm.',
            'is_active' => true,
            'has_gear_detail' => false,
            'sort_order' => 999,
        ]);
    }
}
