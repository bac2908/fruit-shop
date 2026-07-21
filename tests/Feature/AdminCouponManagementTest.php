<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserVoucher;
use App\Support\LocalDateTime;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCouponManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_cannot_access_or_mutate_admin_coupons(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $coupon = $this->coupon();

        $this->actingAs($customer)->get(route('admin.coupons'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.coupons.create'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.coupons.show', $coupon))->assertForbidden();
        $this->actingAs($customer)->patch(route('admin.coupons.toggle', $coupon))->assertForbidden();
    }

    public function test_admin_can_filter_and_open_coupon_screens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = $this->coupon(['title' => 'Voucher lọc dành cho admin']);

        $this->actingAs($admin)
            ->get(route('admin.coupons', ['q' => $coupon->code, 'type' => Coupon::TYPE_PERCENT]))
            ->assertOk()
            ->assertSee($coupon->code)
            ->assertSee('Voucher và khuyến mãi');
        $this->actingAs($admin)->get(route('admin.coupons.create'))->assertOk()->assertSee('Tạo voucher');
        $this->actingAs($admin)->get(route('admin.coupons.show', $coupon))->assertOk()->assertSee('Lịch sử sử dụng');
        $this->actingAs($admin)->get(route('admin.coupons.edit', $coupon))->assertOk()->assertSee('Chỉnh sửa '.$coupon->code);
    }

    public function test_admin_can_create_a_percent_coupon_with_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $code = 'ADMIN'.Str::upper(Str::random(8));

        $response = $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'title' => 'Giảm 12% cho đơn trái cây',
            'code' => $code,
            'type' => Coupon::TYPE_PERCENT,
            'value' => 12,
            'min_order_total' => 400000,
            'max_discount' => 80000,
            'usage_limit' => 100,
            'per_customer_limit' => 1,
            'starts_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'is_active' => 1,
            'is_public' => 1,
            'description' => 'Áp dụng cho đơn đủ điều kiện.',
        ]);

        $coupon = Coupon::query()->where('code', $code)->firstOrFail();
        $response->assertRedirect(route('admin.coupons.show', $coupon))->assertSessionHasNoErrors();
        $this->assertSame(12, $coupon->value);
        $this->assertSame(80000, $coupon->max_discount);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_coupon_created',
        ]);
    }

    public function test_used_coupon_financial_rules_are_immutable_but_copy_can_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = $this->coupon(['used_count' => 1, 'usage_limit' => 10]);
        CouponUsage::query()->create([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'customer_email' => 'used@example.com',
            'discount_total' => 10000,
            'used_at' => now(),
        ]);

        $payload = $this->updatePayload($coupon, ['value' => 25]);
        $this->actingAs($admin)
            ->from(route('admin.coupons.edit', $coupon))
            ->put(route('admin.coupons.update', $coupon), $payload)
            ->assertRedirect(route('admin.coupons.edit', $coupon))
            ->assertSessionHasErrors('coupon');
        $this->assertSame(10, $coupon->fresh()->value);

        $payload = $this->updatePayload($coupon, [
            'title' => 'Tên voucher đã được làm rõ',
            'usage_limit' => 20,
        ]);
        $this->actingAs($admin)
            ->put(route('admin.coupons.update', $coupon), $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame('Tên voucher đã được làm rõ', $coupon->fresh()->title);
        $this->assertSame(20, $coupon->fresh()->usage_limit);
    }

    public function test_usage_limit_cannot_be_lower_than_existing_usage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = $this->coupon(['used_count' => 3, 'usage_limit' => 10]);

        $this->actingAs($admin)
            ->from(route('admin.coupons.edit', $coupon))
            ->put(route('admin.coupons.update', $coupon), $this->updatePayload($coupon, ['usage_limit' => 2]))
            ->assertRedirect(route('admin.coupons.edit', $coupon))
            ->assertSessionHasErrors('usage_limit');

        $this->assertSame(10, $coupon->fresh()->usage_limit);
    }

    public function test_admin_assigns_to_all_customers_without_duplicates_or_admin_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customers = User::factory()->count(2)->create(['role' => 'customer']);
        $coupon = $this->coupon(['is_public' => false]);

        $payload = [
            'coupon_id' => $coupon->id,
            'target' => 'all_customers',
            'expires_at' => now()->addWeek()->format('Y-m-d\TH:i'),
        ];
        $customerCount = User::query()->where('role', 'customer')->count();
        $this->actingAs($admin)->post(route('admin.coupons.assign'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.coupons.assign'), $payload)->assertSessionHasNoErrors();

        $this->assertSame($customerCount, UserVoucher::query()->where('coupon_id', $coupon->id)->count());
        $this->assertFalse(UserVoucher::query()->where('coupon_id', $coupon->id)->where('user_id', $admin->id)->exists());
        foreach ($customers as $customer) {
            $this->assertSame(1, UserVoucher::query()->where('coupon_id', $coupon->id)->where('user_id', $customer->id)->count());
            $this->assertTrue($customer->notifications()->where('data->related_id', $coupon->id)->exists());
        }
        $this->assertDatabaseHas('security_audit_log', ['user_id' => $admin->id, 'action' => 'admin_coupon_assigned']);
    }

    public function test_archive_and_restore_keep_history_and_return_coupon_as_inactive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = $this->coupon();

        $this->actingAs($admin)->delete(route('admin.coupons.destroy', $coupon))->assertRedirect(route('admin.coupons'));
        $this->assertSoftDeleted('coupons', ['id' => $coupon->id]);

        $this->actingAs($admin)
            ->patch(route('admin.coupons.restore', $coupon->id))
            ->assertRedirect(route('admin.coupons.edit', $coupon->id));

        $restored = Coupon::query()->findOrFail($coupon->id);
        $this->assertFalse($restored->is_active);
        $this->assertDatabaseHas('security_audit_log', ['user_id' => $admin->id, 'action' => 'admin_coupon_archived']);
        $this->assertDatabaseHas('security_audit_log', ['user_id' => $admin->id, 'action' => 'admin_coupon_restored']);
    }

    public function test_gift_coupon_reserves_and_restores_real_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $category = Category::query()->create([
            'name' => 'Danh mục voucher admin '.Str::random(6),
            'slug' => 'voucher-admin-'.Str::lower(Str::random(8)),
            'is_active' => true,
            'sort_order' => 999,
        ]);
        $cartProduct = $this->product($category, ['name' => 'Giỏ trái cây kiểm thử', 'stock' => 5, 'price' => 600000]);
        $giftProduct = $this->product($category, ['name' => 'Kiwi quà tặng kiểm thử', 'stock' => 3, 'price' => 90000]);
        $coupon = $this->coupon([
            'type' => Coupon::TYPE_GIFT,
            'value' => 180000,
            'gift_product_id' => $giftProduct->id,
            'gift_quantity' => 2,
            'min_order_total' => 500000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.coupons', ['status' => 'active', 'type' => Coupon::TYPE_GIFT]))
            ->assertOk()
            ->assertSee($coupon->code);

        $this->actingAs($customer)
            ->withSession([
                'cart' => [(string) $cartProduct->id => ['product_id' => $cartProduct->id, 'quantity' => 1]],
                'cart_coupon_code' => $coupon->code,
                'cart_coupon_selection_mode' => 'manual',
                'checkout_shipping' => [
                    'customer_name' => $customer->name,
                    'customer_phone' => '+84912345678',
                    'customer_email' => $customer->email,
                    'shipping_address' => '123 Đường Kiểm Thử, TP.HCM',
                    'province_code' => '79',
                    'ward_code' => 'test-ward',
                    'ward' => 'Phường Test',
                    'notes' => null,
                    'save_address' => false,
                ],
            ])
            ->post(route('checkout.place'), ['payment_method' => Order::PAYMENT_METHOD_COD])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertSame(1, $giftProduct->fresh()->stock);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $giftProduct->id,
            'qty' => 2,
            'unit_price' => 0,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'order_id' => $order->id,
            'product_id' => $giftProduct->id,
            'type' => 'coupon_gift',
            'quantity' => -2,
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => Order::STATUS_CANCELLED,
            'admin_note' => 'Hủy đơn kiểm thử để hoàn kho quà.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(3, $giftProduct->fresh()->stock);
    }

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::query()->create(array_merge([
            'title' => 'Voucher admin kiểm thử',
            'code' => 'CP'.Str::upper(Str::random(12)),
            'type' => Coupon::TYPE_PERCENT,
            'value' => 10,
            'min_order_total' => 100000,
            'max_discount' => 50000,
            'usage_limit' => 100,
            'per_customer_limit' => 1,
            'used_count' => 0,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
            'is_public' => true,
            'description' => 'Voucher dùng trong kiểm thử quản trị.',
            'gift_quantity' => 1,
        ], $overrides));
    }

    private function updatePayload(Coupon $coupon, array $overrides = []): array
    {
        return array_merge([
            'title' => $coupon->title,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'gift_product_id' => $coupon->gift_product_id,
            'gift_quantity' => $coupon->gift_quantity ?: 1,
            'min_order_total' => $coupon->min_order_total,
            'max_discount' => $coupon->max_discount,
            'usage_limit' => $coupon->usage_limit,
            'per_customer_limit' => $coupon->per_customer_limit,
            'starts_at' => LocalDateTime::format($coupon->starts_at, 'Y-m-d\TH:i', ''),
            'ends_at' => LocalDateTime::format($coupon->ends_at, 'Y-m-d\TH:i', ''),
            'is_active' => (int) $coupon->is_active,
            'is_public' => (int) $coupon->is_public,
            'description' => $coupon->description,
        ], $overrides);
    }

    private function product(Category $category, array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'Sản phẩm voucher '.Str::random(6),
            'slug' => 'san-pham-voucher-'.Str::lower(Str::random(10)),
            'sku' => 'VOUCHER-'.Str::upper(Str::random(10)),
            'unit' => 'hộp',
            'stock' => 10,
            'price' => 100000,
            'is_active' => true,
            'sort_order' => 999,
        ], $overrides));
    }
}
