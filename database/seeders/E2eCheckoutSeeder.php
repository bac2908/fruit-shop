<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2eCheckoutSeeder extends Seeder
{
    public const USER_EMAIL = 'checkout.e2e@example.test';
    public const USER_PASSWORD = 'Checkout#2026';
    public const PRODUCT_SLUG = 'e2e-checkout-product';

    public function run(): void
    {
        $category = Category::query()->create([
            'name' => 'Sản phẩm kiểm thử E2E',
            'slug' => 'e2e-checkout',
            'sort_order' => 999,
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Hộp trái cây kiểm thử checkout',
            'slug' => self::PRODUCT_SLUG,
            'sku' => 'E2E-CHECKOUT-001',
            'unit' => 'hộp',
            'stock' => 20,
            'low_stock_threshold' => 3,
            'price' => 180000,
            'thumb' => 'images/sliders/banner_custom_1.webp',
            'short_desc' => 'Sản phẩm chỉ dùng cho kiểm thử checkout tự động.',
            'description' => '<p>Sản phẩm chỉ tồn tại trong database E2E tách biệt.</p>',
            'is_active' => true,
            'sort_order' => 1,
            'meta_title' => 'Hộp trái cây kiểm thử checkout',
            'meta_description' => 'Sản phẩm dùng cho kiểm thử checkout end-to-end.',
        ]);

        $user = User::query()->create([
            'name' => 'Khách hàng E2E',
            'email' => self::USER_EMAIL,
            'email_verified_at' => now(),
            'password' => Hash::make(self::USER_PASSWORD),
            'phone' => '+84901234567',
            'role' => 'customer',
            'password_changed_at' => now(),
            'force_password_change' => false,
        ]);

        UserAddress::query()->create([
            'user_id' => $user->id,
            'recipient_name' => 'Khách hàng E2E',
            'phone' => '+84901234567',
            'address_line' => '12 Phố Kiểm Thử',
            'ward' => 'Phường Ba Đình',
            'district' => null,
            'province' => 'Thành phố Hà Nội',
            'province_code' => '01',
            'ward_code' => '00004',
            'is_default' => true,
        ]);
    }
}
