<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use App\Models\CouponImage;
use App\Models\User;
use App\Services\WelcomeVoucherService;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $endsAt = now()->addMonths(3)->endOfDay();
        $startsAt = now()->subDay()->startOfDay();

        $coupons = [
            [
                'title' => 'Tặng 500g Kiwi vàng New Zealand',
                'code' => 'KIWIVANG500',
                'type' => Coupon::TYPE_GIFT,
                'value' => 90000,
                'min_order_total' => 500000,
                'description' => 'Nhận miễn phí 500g Kiwi vàng New Zealand khi đơn hàng đạt từ 500.000đ.',
                'image_url' => '//theme.hstatic.net/200000157781/1001036201/14/icon_coupon_1.png?v=1061',
            ],
            [
                'title' => 'Tặng 1kg Quýt Thái',
                'code' => 'QUYTTHAI1KG',
                'type' => Coupon::TYPE_GIFT,
                'value' => 110000,
                'min_order_total' => 800000,
                'description' => 'Nhận miễn phí 1kg Quýt Thái khi đơn hàng đạt từ 800.000đ.',
                'image_url' => '//theme.hstatic.net/200000157781/1001036201/14/icon_coupon_2.png?v=1061',
            ],
            [
                'title' => 'Giảm 10% cho đơn hàng',
                'code' => 'GIOQUA10',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 10,
                'min_order_total' => 300000,
                'description' => 'Giảm 10%, tối đa 100.000đ cho đơn hàng đạt từ 300.000đ.',
                'image_url' => '//theme.hstatic.net/200000157781/1001036201/14/icon_coupon_3.png?v=1061',
                'max_discount' => 100000,
            ],
        ];

        foreach ($coupons as $couponData) {
            $imageUrl = $couponData['image_url'];
            unset($couponData['image_url']);

            $coupon = Coupon::query()->firstOrNew(['code' => $couponData['code']]);
            $coupon->fill(array_merge($couponData, [
                'is_active' => true,
                'is_public' => true,
                'per_customer_limit' => 1,
            ]));

            if (!$coupon->exists) {
                $coupon->starts_at = $startsAt;
                $coupon->ends_at = $endsAt;
                $coupon->used_count = 0;
            }

            $coupon->save();

            CouponImage::query()->updateOrCreate(
                [
                    'coupon_id' => $coupon->id,
                    'sort_order' => 0,
                ],
                [
                    'url' => $imageUrl,
                ]
            );
        }

        $welcomeVouchers = app(WelcomeVoucherService::class);
        User::query()
            ->where('role', 'customer')
            ->chunkById(200, function ($users) use ($welcomeVouchers) {
                foreach ($users as $user) {
                    $welcomeVouchers->assignTo($user);
                }
            });

        if ($this->command) {
            $this->command->info('Active coupons seeded/refreshed: ' . count($coupons));
        }
    }
}
