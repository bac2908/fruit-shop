<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserVoucher;
use App\Services\WelcomeVoucherService;
use App\Services\VoucherSelectionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CouponMemberFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_receives_three_welcome_vouchers_without_duplicates(): void
    {
        $this->ensureWelcomeCoupons();
        $user = User::factory()->create(['role' => 'customer']);
        $service = app(WelcomeVoucherService::class);

        $service->assignTo($user);
        $service->assignTo($user);

        $this->assertSame(
            3,
            UserVoucher::query()
                ->where('user_id', $user->id)
                ->whereIn('coupon_id', Coupon::whereIn('code', WelcomeVoucherService::CODES)->pluck('id'))
                ->count()
        );
        $this->assertSame(3, $user->notifications()->count());

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Bạn đã nhận voucher GIOQUA10')
            ->assertSee('Bạn đã nhận voucher QUYTTHAI1KG')
            ->assertSee('Bạn đã nhận voucher KIWIVANG500');

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_system_recommends_direct_discount_and_explains_locked_vouchers(): void
    {
        $this->ensureWelcomeCoupons();
        $user = User::factory()->create(['role' => 'customer']);
        app(WelcomeVoucherService::class)->assignTo($user);

        $options = app(VoucherSelectionService::class)->optionsFor($user, 600000);
        $best = app(VoucherSelectionService::class)->bestEligible($options);
        $quytOption = $options->first(function (array $option) {
            return $option['coupon']->code === 'QUYTTHAI1KG';
        });

        $this->assertSame('GIOQUA10', optional($best)->code);
        $this->assertFalse($quytOption['eligible']);
        $this->assertSame(200000, $quytOption['missing_amount']);
        $this->assertSame('Mua thêm 200.000đ để sử dụng.', $quytOption['reason']);
    }

    public function test_customer_cannot_open_another_customers_notification(): void
    {
        $this->ensureWelcomeCoupons();
        $owner = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        app(WelcomeVoucherService::class)->assignTo($owner);
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($otherCustomer)
            ->post(route('notifications.open', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_cart_automatically_applies_the_recommended_voucher(): void
    {
        $this->ensureWelcomeCoupons();
        $user = User::factory()->create(['role' => 'customer']);
        app(WelcomeVoucherService::class)->assignTo($user);
        $category = Category::query()->create([
            'name' => 'Danh mục tự chọn voucher',
            'slug' => 'danh-muc-tu-chon-voucher-' . uniqid(),
            'is_active' => true,
            'sort_order' => 999,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Giỏ hàng tự chọn voucher',
            'slug' => 'gio-hang-tu-chon-voucher-' . uniqid(),
            'sku' => 'AUTO-' . strtoupper(uniqid()),
            'unit' => 'hộp',
            'stock' => 10,
            'price' => 600000,
            'is_active' => true,
            'sort_order' => 999,
        ]);

        $this->actingAs($user)
            ->withSession([
                'cart' => [
                    (string) $product->id => [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->get(route('cart'))
            ->assertOk()
            ->assertSee('Đã tự động chọn ưu đãi tốt nhất')
            ->assertSee('Mua thêm 200.000đ để sử dụng.');

        $this->assertSame('GIOQUA10', session('cart_coupon_code'));
        $this->assertSame('auto', session('cart_coupon_selection_mode'));
    }

    public function test_used_voucher_is_rejected_and_shown_as_used(): void
    {
        $this->ensureWelcomeCoupons();
        $user = User::factory()->create(['role' => 'customer']);
        $coupon = Coupon::where('code', 'GIOQUA10')->firstOrFail();

        CouponUsage::query()->create([
            'coupon_id' => $coupon->id,
            'order_id' => null,
            'user_id' => $user->id,
            'coupon_code' => $coupon->code,
            'customer_email' => $user->email,
            'discount_total' => 30000,
            'used_at' => now(),
        ]);

        app(WelcomeVoucherService::class)->assignTo($user);

        $this->assertTrue($coupon->hasBeenUsedBy($user->id, $user->email));
        $this->assertSame(
            'Mã GIOQUA10 đã được tài khoản của bạn sử dụng.',
            $coupon->getInvalidReason(300000, $user->id, $user->email)
        );
        $this->assertNotNull(
            UserVoucher::where('user_id', $user->id)
                ->where('coupon_id', $coupon->id)
                ->value('used_at')
        );

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Đã sử dụng')
            ->assertSee('GIOQUA10');
    }

    public function test_gift_coupon_creates_a_free_gift_line_in_the_order(): void
    {
        $this->ensureWelcomeCoupons();
        $user = User::factory()->create(['role' => 'customer']);
        app(WelcomeVoucherService::class)->assignTo($user);

        $category = Category::query()->create([
            'name' => 'Danh mục test voucher',
            'slug' => 'danh-muc-test-voucher-' . uniqid(),
            'is_active' => true,
            'sort_order' => 999,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm test voucher',
            'slug' => 'san-pham-test-voucher-' . uniqid(),
            'sku' => 'TEST-' . strtoupper(uniqid()),
            'unit' => 'hộp',
            'stock' => 10,
            'price' => 600000,
            'is_active' => true,
            'sort_order' => 999,
        ]);

        $this->actingAs($user)
            ->withSession([
                'cart' => [
                    (string) $product->id => [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'cart_coupon_code' => 'KIWIVANG500',
                'checkout_shipping' => [
                    'customer_name' => $user->name,
                    'customer_phone' => '+84912345678',
                    'customer_email' => $user->email,
                    'shipping_address' => '123 Đường Test, Phường Test, TP.HCM',
                    'province_code' => '79',
                    'ward_code' => 'test-ward',
                    'ward' => 'Phường Test',
                    'notes' => null,
                    'save_address' => false,
                ],
            ])
            ->post(route('checkout.place'), [
                'payment_method' => Order::PAYMENT_METHOD_COD,
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->assertSame('KIWIVANG500', $order->coupon_code);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Tặng 500g Kiwi vàng New Zealand',
            'unit' => 'quà tặng voucher',
            'unit_price' => 0,
            'qty' => 1,
            'line_total' => 0,
        ]);
        $this->assertSame(2, OrderItem::where('order_id', $order->id)->count());
        $this->assertTrue($user->notifications()->where('data->event_key', 'order:' . $order->id . ':placed')->exists());
        $this->assertTrue($user->notifications()->where('data->event_key', 'order:' . $order->id . ':status:confirmed')->exists());
    }

    private function ensureWelcomeCoupons(): void
    {
        $definitions = [
            ['code' => 'GIOQUA10', 'title' => 'Giảm 10% cho đơn hàng', 'type' => Coupon::TYPE_PERCENT, 'value' => 10, 'min_order_total' => 300000, 'max_discount' => 100000],
            ['code' => 'QUYTTHAI1KG', 'title' => 'Tặng 1kg Quýt Thái', 'type' => Coupon::TYPE_GIFT, 'value' => 110000, 'min_order_total' => 800000, 'max_discount' => null],
            ['code' => 'KIWIVANG500', 'title' => 'Tặng 500g Kiwi vàng New Zealand', 'type' => Coupon::TYPE_GIFT, 'value' => 90000, 'min_order_total' => 500000, 'max_discount' => null],
        ];

        foreach ($definitions as $definition) {
            Coupon::query()->updateOrCreate(
                ['code' => $definition['code']],
                array_merge($definition, [
                    'description' => $definition['title'],
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addMonth(),
                    'is_active' => true,
                    'is_public' => true,
                    'per_customer_limit' => 1,
                ])
            );
        }
    }
}
