<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use App\Models\CouponImage;

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
                'title' => 'Tang ngay 500gr Kiwi vang New Zealand',
                'code' => 'KIWIVANG500',
                'type' => Coupon::TYPE_GIFT,
                'value' => null,
                'min_order_total' => 500000,
                'description' => 'Ap dung cho don hang tu 500.000d, qua tang duoc xac nhan khi goi dien.',
                'image_url' => '//theme.hstatic.net/200000157781/1001036201/14/icon_coupon_1.png?v=1061',
            ],
            [
                'title' => 'Tang 1kg Quyt Thai cho don tu 800k',
                'code' => 'QUYTTHAI1KG',
                'type' => Coupon::TYPE_GIFT,
                'value' => null,
                'min_order_total' => 800000,
                'description' => 'Qua tang theo mua, ap dung den khi het so luong khuyen mai.',
                'image_url' => '//theme.hstatic.net/200000157781/1001036201/14/icon_coupon_2.png?v=1061',
            ],
            [
                'title' => 'Giam 10% cho don gio qua trai cay',
                'code' => 'GIOQUA10',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 10,
                'min_order_total' => 300000,
                'description' => 'Giam toi da 100.000d cho cac don gio qua trai cay.',
                'image_url' => '//theme.hstatic.net/200000157781/1001036201/14/icon_coupon_3.png?v=1061',
                'max_discount' => 100000,
            ],
        ];

        foreach ($coupons as $couponData) {
            $imageUrl = $couponData['image_url'];
            unset($couponData['image_url']);

            $coupon = Coupon::query()->updateOrCreate(
                ['code' => $couponData['code']],
                array_merge($couponData, [
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'is_active' => true,
                    'used_count' => 0,
                ])
            );

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

        if ($this->command) {
            $this->command->info('Active coupons seeded/refreshed: ' . count($coupons));
        }
    }
}
