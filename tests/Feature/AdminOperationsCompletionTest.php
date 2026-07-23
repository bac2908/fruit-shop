<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminActionCenterService;
use App\Services\AdminPermissionService;
use App\Services\SettingService;
use App\Services\ShippingFeeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOperationsCompletionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reports_use_real_orders_and_export_csv(): void
    {
        $admin = $this->admin();
        $order = $this->order([
            'status' => Order::STATUS_DONE,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'total' => 345000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports', [
                'from' => now()->subDay()->toDateString(),
                'to' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('345.000');

        $csvResponse = $this->actingAs($admin)
            ->get(route('admin.reports.export', [
                'from' => now()->subDay()->toDateString(),
                'to' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString($order->code, $csvResponse->streamedContent());
    }

    public function test_settings_are_persisted_and_used_by_shipping_service(): void
    {
        $admin = $this->admin();
        $payload = [
            'store_name' => 'FruitShop Test',
            'store_hotline' => '0909123456',
            'store_email' => 'support@example.test',
            'store_address' => '123 Đường Kiểm Thử',
            'display_timezone' => 'Asia/Ho_Chi_Minh',
            'shipping_free_threshold' => 900000,
            'shipping_default_fee' => 88000,
            'shipping_remote_surcharge' => 33000,
            'low_stock_default_threshold' => 7,
            'payment_cod_enabled' => '1',
            'payment_bank_enabled' => '1',
            'email_order_placed_enabled' => '1',
            'email_order_confirmed_enabled' => '1',
            'email_order_cancelled_enabled' => '1',
            'low_stock_alert_enabled' => '1',
        ];

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'store_name', 'value' => 'FruitShop Test']);
        $this->assertFalse(app(SettingService::class)->bool('payment_momo_enabled'));
        $quote = app(ShippingFeeService::class)->quote(100000, 'province-not-in-config');
        $this->assertSame(88000, $quote['fee']);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), array_merge($payload, [
                'payment_cod_enabled' => null,
                'payment_bank_enabled' => null,
            ]))
            ->assertSessionHasErrors('payment_methods');
    }

    public function test_admin_content_crud_is_safe_and_visible_on_storefront(): void
    {
        $admin = $this->admin();
        $slug = 'trang-test-'.Str::lower(Str::random(8));

        $this->actingAs($admin)
            ->post(route('admin.pages.store'), [
                'title' => 'Trang kiểm thử nội dung',
                'slug' => $slug,
                'excerpt' => 'Nội dung tóm tắt.',
                'content' => '<script>alert(1)</script><p onclick="bad()">Nội dung <a href="javascript:alert(2)">an toàn</a></p>',
                'meta_title' => 'SEO trang kiểm thử',
                'meta_description' => 'Mô tả SEO.',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', $slug)->firstOrFail();
        $this->assertStringNotContainsStringIgnoringCase('<script', $page->content);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $page->content);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $page->content);

        $this->get(route('page.dynamic', $slug))
            ->assertOk()
            ->assertSee('Nội dung')
            ->assertSee('an toàn')
            ->assertDontSee('alert(1)', false);

        $banner = Banner::query()->create([
            'placement' => 'hero',
            'title' => 'Hero quản trị',
            'alt_text' => 'Ảnh hero quản trị',
            'image_url' => 'https://cdn.example.test/admin-hero.webp',
            'link_url' => '/collections/all',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($banner->image_url, false)
            ->assertSee($banner->alt_text);
    }

    public function test_rbac_blocks_direct_urls_and_search_only_returns_authorized_data(): void
    {
        $catalog = $this->admin(AdminPermissionService::ROLE_CATALOG);
        $analyst = $this->admin(AdminPermissionService::ROLE_ANALYST);
        $customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'private-customer-'.Str::random(5).'@example.test',
        ]);
        $product = $this->product(['name' => 'Nho tìm kiếm RBAC']);

        $this->actingAs($catalog)->get(route('admin.products'))->assertOk();
        $this->actingAs($catalog)->get(route('admin.orders'))->assertForbidden();
        $this->actingAs($catalog)
            ->getJson(route('admin.search.suggestions', ['q' => 'Nho tìm kiếm']))
            ->assertOk()
            ->assertJsonFragment(['title' => $product->name]);
        $searchResponse = $this->actingAs($catalog)
            ->get(route('admin.search', ['q' => $customer->email]))
            ->assertOk();
        $this->assertStringNotContainsString(
            route('admin.customers.show', $customer),
            $searchResponse->getContent()
        );

        $this->actingAs($analyst)->get(route('admin.reports'))->assertOk();
        $this->actingAs($analyst)->get(route('admin.products'))->assertForbidden();
        $this->actingAs($analyst)->put(route('admin.settings.update'), [])->assertForbidden();
    }

    public function test_super_admin_can_create_staff_and_temporary_password_is_forced_to_change(): void
    {
        $superAdmin = $this->admin();
        $email = 'staff-'.Str::lower(Str::random(8)).'@example.test';

        $this->actingAs($superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Nhân viên catalog',
                'email' => $email,
                'admin_role' => AdminPermissionService::ROLE_CATALOG,
                'password' => 'Strong#Staff2026',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $staff = User::query()->where('email', $email)->firstOrFail();
        $this->assertTrue($staff->force_password_change);
        $this->assertSame(AdminPermissionService::ROLE_CATALOG, $staff->admin_role);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.security.password.edit'));
    }

    public function test_totp_setup_challenge_and_replay_protection_work(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.2fa.setup'))->assertOk();
        $secret = (string) session('admin_2fa_pending_secret');
        $this->assertNotSame('', $secret);

        $currentStep = intdiv(time(), 30);
        $this->actingAs($admin)
            ->post(route('admin.2fa.confirm'), ['code' => $this->totpCode($secret, $currentStep)])
            ->assertRedirect(route('admin.2fa.setup'))
            ->assertSessionHas('two_factor_recovery_codes');

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactorAuthentication());

        session()->forget('admin_2fa_user_id');
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.2fa.challenge'));

        $this->actingAs($admin)
            ->post(route('admin.2fa.challenge.verify'), ['code' => $this->totpCode($secret, $currentStep)])
            ->assertSessionHasErrors('code');

        $this->actingAs($admin)
            ->post(route('admin.2fa.challenge.verify'), ['code' => $this->totpCode($secret, $currentStep + 1)])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_action_center_respects_permissions(): void
    {
        $catalog = $this->admin(AdminPermissionService::ROLE_CATALOG);
        $product = $this->product([
            'name' => 'Sản phẩm tồn thấp quyền catalog',
            'stock' => 1,
            'low_stock_threshold' => 3,
            'is_active' => true,
        ]);
        $this->order(['status' => Order::STATUS_PENDING]);

        $summary = app(AdminActionCenterService::class)->summary(user: $catalog);
        $this->assertGreaterThanOrEqual(1, $summary['counts']['low_stock']);
        $this->assertSame(0, $summary['counts']['pending_orders']);
        $this->assertTrue($summary['items']->contains(fn (array $item) => str_contains($item['title'], $product->name)));
    }

    private function admin(?string $adminRole = null): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_role' => $adminRole,
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
            'force_password_change' => false,
        ]);
    }

    private function product(array $overrides = []): Product
    {
        $token = Str::lower(Str::random(10));
        $category = Category::query()->firstOrCreate(
            ['slug' => 'admin-operations-test'],
            ['name' => 'Admin operations test', 'sort_order' => 9999, 'is_active' => true]
        );

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'Sản phẩm '.$token,
            'slug' => 'product-'.$token,
            'sku' => 'OPS-'.Str::upper($token),
            'unit' => 'kg',
            'stock' => 5,
            'low_stock_threshold' => 2,
            'price' => 120000,
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    private function order(array $overrides = []): Order
    {
        $token = Str::upper(Str::random(10));

        return Order::query()->create(array_merge([
            'code' => 'OPS-'.$token,
            'customer_name' => 'Khách kiểm thử',
            'customer_phone' => '0909123456',
            'customer_email' => 'buyer-'.$token.'@example.test',
            'shipping_address' => '123 Đường Test',
            'subtotal' => 300000,
            'shipping_fee' => 45000,
            'discount_total' => 0,
            'total' => 345000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'shipping_status' => Order::SHIPPING_STATUS_PENDING,
            'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_CONFIRMED,
        ], $overrides));
    }

    private function totpCode(string $secret, int $step): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $character) {
            $position = strpos($alphabet, $character);
            $bits .= str_pad(decbin((int) $position), 5, '0', STR_PAD_LEFT);
        }
        $binarySecret = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $binarySecret .= chr(bindec($chunk));
            }
        }

        $hash = hash_hmac('sha1', pack('N2', 0, $step), $binarySecret, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = (
            ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff)
        ) % 1_000_000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }
}
