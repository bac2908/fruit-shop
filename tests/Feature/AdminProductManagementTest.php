<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_cannot_access_or_mutate_admin_products(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.products'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.products.store'), [])
            ->assertForbidden();
    }

    public function test_admin_can_open_product_index_create_and_edit_screens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();

        $this->actingAs($admin)
            ->get(route('admin.products'))
            ->assertOk()
            ->assertSee('Quản lý sản phẩm');

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Tạo sản phẩm');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_admin_can_create_product_with_image_inventory_and_audit_history(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->category();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'category_id' => $category->id,
            'images' => [UploadedFile::fake()->image('mang-cut.jpg', 800, 800)],
            'stock' => 12,
            'is_active' => '1',
        ]));

        $product = Product::query()->where('sku', 'TGC-ADMIN-001')->firstOrFail();
        $response->assertRedirect(route('admin.products.edit', $product));

        $this->assertSame('mang-cut-lai-thieu-admin', $product->slug);
        $this->assertTrue($product->is_active);
        $this->assertSame(1, $product->images()->count());
        $this->assertStringStartsWith('storage/products/'.$product->id.'/', (string) $product->thumb);
        Storage::disk('public')->assertExists(Str::after($product->thumb, 'storage/'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => 'initial_stock',
            'quantity' => 12,
            'stock_before' => 0,
            'stock_after' => 12,
        ]);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_product_created',
        ]);
    }

    public function test_admin_update_records_stock_adjustment_and_rejects_invalid_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['stock' => 5, 'price' => 120000]);
        $product->images()->create([
            'url' => 'https://cdn.example.test/admin-product.jpg',
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->payload([
                'sku' => $product->sku,
                'slug' => $product->slug,
                'stock' => 14,
                'price' => 130000,
                'sale_price' => 110000,
                'stock_note' => 'Kiểm kê và nhập thêm 9 sản phẩm.',
            ]))
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertSame(14, $product->stock);
        $this->assertSame(130000, $product->price);
        $this->assertSame(110000, $product->sale_price);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'admin_adjustment',
            'quantity' => 9,
            'stock_before' => 5,
            'stock_after' => 14,
        ]);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_product_updated',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $this->payload([
                'sku' => $product->sku,
                'slug' => $product->slug,
                'stock' => 99,
                'price' => 100000,
                'sale_price' => 120000,
            ]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('sale_price');

        $this->assertSame(14, $product->fresh()->stock);
    }

    public function test_active_product_cannot_lose_its_last_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['is_active' => true]);
        $image = $product->images()->create([
            'url' => 'https://cdn.example.test/only-image.jpg',
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->delete(route('admin.products.images.destroy', [$product, $image]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('images');

        $this->assertDatabaseHas('product_images', ['id' => $image->id]);
    }

    public function test_admin_soft_deletes_and_restores_product_as_hidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['is_active' => true]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_product_deleted',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.products.restore', $product->id))
            ->assertRedirect();

        $restored = Product::query()->findOrFail($product->id);
        $this->assertFalse($restored->is_active);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_product_restored',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category()->id,
            'name' => 'Măng cụt Lái Thiêu Admin',
            'slug' => 'mang-cut-lai-thieu-admin',
            'sku' => 'tgc-admin-001',
            'unit' => 'kg',
            'stock' => 5,
            'low_stock_threshold' => 3,
            'price' => 120000,
            'sale_price' => null,
            'cost_price' => 80000,
            'short_desc' => 'Măng cụt tươi dành cho kiểm thử trang quản trị.',
            'description' => '<p>Măng cụt tươi, được kiểm tra chất lượng trước khi giao.</p>',
            'sort_order' => 0,
            'meta_title' => 'Măng cụt Lái Thiêu',
            'meta_description' => 'Măng cụt Lái Thiêu tươi ngon, giao hàng tận nơi.',
            'is_active' => '0',
            'has_gear_detail' => '0',
            'stock_note' => 'Kiểm thử quản lý tồn kho.',
        ], $overrides);
    }

    private function category(): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => 'admin-product-test-category'],
            [
                'name' => 'Danh mục kiểm thử admin',
                'sort_order' => 999,
                'is_active' => true,
            ]
        );
    }

    private function product(array $overrides = []): Product
    {
        $unique = Str::lower(Str::random(10));

        return Product::query()->create(array_merge([
            'category_id' => $this->category()->id,
            'name' => 'Sản phẩm quản trị '.$unique,
            'slug' => 'admin-product-'.$unique,
            'sku' => 'ADMIN-'.Str::upper($unique),
            'unit' => 'kg',
            'stock' => 5,
            'low_stock_threshold' => 3,
            'price' => 120000,
            'sale_price' => null,
            'cost_price' => 80000,
            'short_desc' => 'Sản phẩm dùng để kiểm thử quản trị.',
            'description' => '<p>Sản phẩm dùng để kiểm thử quản trị.</p>',
            'sort_order' => 0,
            'is_active' => false,
            'has_gear_detail' => false,
        ], $overrides));
    }
}
