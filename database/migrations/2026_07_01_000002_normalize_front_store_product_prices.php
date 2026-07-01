<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        $prices = [
            // Obvious import mistakes on common fresh fruit.
            'chuoi-gia-huong' => [35000, null],
            'chuoi-cha-bot' => [40000, null],
            'chuoi-cau' => [35000, null],
            'chuoi-su' => [35000, null],
            'dua-hau-dai-ruot-do' => [25000, null],
            'dua-hau-khong-hat-mat-troi-do' => [35000, null],
            'cam-sanh-ben-tre' => [35000, null],
            'thanh-long-ruot-do' => [45000, null],
            'oi-nu-hoang' => [45000, null],

            // Friendlier first-impression prices for local fruit.
            'dua-le-bach-kim' => [55000, null],
            'du-du-baby-ruot-vang' => [45000, null],
            'du-du-ruot-vang-long-an' => [45000, null],
            'dua-luoi-ruot-xanh-ichiba-kg' => [95000, null],
            'dua-luoi-mat-egarden' => [130000, 90000],
            'dua-xiem-xanh-ben-tre-tui-luoi-3-qua' => [120000, null],
            'quyt-tieu-vang' => [70000, null],
            'bo-mini-khong-hat' => [120000, null],
            'man-tron-hong-dao' => [120000, null],
            'man-xanh-duong' => [100000, null],
            'man-hong-soc-trang' => [120000, 100000],
            'nhan-hat-tieu-tieu-long-kg' => [120000, 95000],
            'bon-bon-thai-1kg' => [120000, null],
            'mang-cut-loc-ninh' => [160000, 120000],

            // Premium imported fruit stays premium, but remove placeholder-level prices.
            'sau-rieng-musangking-kg-nguyen-trai-chua-tach-vo' => [220000, 160000],
            'dau-han-quoc-kfarm-500gr-hop' => [380000, null],
            'dau-bach-tuyet-han-quoc-hop-330gr' => [460000, null],
            'hong-deo-han-quoc-nguyen-trai' => [350000, null],
            'set-hong-deo-han-quoc-20-qua-set' => [650000, null],
            'set-hong-deo-han-quoc-sang-ju-20-qua-set' => [750000, null],
            'nho-mau-don-shine-muscat-greenvil-han-quoc-hop-900gr' => [750000, null],
            'nho-mau-don-han-quoc-eloasis' => [400000, null],

            // Gift baskets and ceremonial trays are starting prices, not fixed final quotes.
            'gio-qua-chuc-mung-sinh-nhat' => [850000, null],
            'gio-qua-trai-cay-chuc-mung-tan-gia' => [1100000, null],
            'gio-qua-trai-cay-khai-truong-hong-phat-1' => [1200000, null],
            'gio-trai-cay-cung-that-49-ngay' => [1100000, null],
            'gio-trai-cay-dam-tang-hd-bank-kinh-vieng' => [1200000, null],
            'gio-trai-cay-di-dam-cbme-kinh-vieng' => [1200000, null],
            'gio-trai-cay-h31' => [950000, null],
            'hop-qua-tang-ngay-phu-nu-viet-nam-20-10' => [950000, null],
            'hop-qua-trai-cay-ms01' => [850000, null],
            'hop-qua-trai-cay-ms02' => [950000, null],
            'hop-qua-trai-cay-ms03' => [1150000, null],
            'hop-qua-trai-cay-ms05' => [1250000, null],
            'hop-qua-trai-cay-ms12' => [1200000, null],
            'set-qua-8-qua-tao-envy-my-size-72' => [900000, null],
            'set-qua-tang-trai-cay' => [950000, null],
            'set-qua-07' => [1050000, null],
            'set-qua-tang-trai-cay-ms08' => [1150000, null],
            'set-qua-tang-trai-cay-ms09' => [1250000, null],
            'set-qua-10' => [1350000, null],
            'set-qua-tet-yen-sao-mix-trai-cay-ys2023-hop' => [1250000, null],
            'gio-qua-tang-15-qua-cam-uc' => [1200000, null],
            'gio-qua-tang-doi-tac-tc1300' => [1300000, null],
            'gio-qua-h780' => [780000, null],
            'gio-qua-trai-cay-va-hoa-hong-chuc-mung-sinh-nhat' => [1200000, null],
            'hop-qua-trai-cay-han-quoc-hq1500' => [1500000, null],
            'hop-qua-trai-cay-luc-giac-hlg01' => [1000000, null],
            'hop-qua-trai-cay-qt1300' => [1300000, null],
            'hop-qua-trai-cay-tang-dong-nghiep-dn1250' => [1250000, null],
            'hop-qua-trai-cay-tang-gia-dinh' => [1200000, null],
            'hop-qua-trai-cay-tang-sinh-nhat-sn1300' => [1300000, null],
            'set-qua-12-qua-tao-envy-newzealand-size-35' => [900000, null],
            'mam-cung-01' => [650000, null],
            'mam-qua-cuoi-trai-cay-01' => [1200000, null],
            'mam-qua-cuoi-trai-cay-02' => [1500000, null],
            'mam-qua-cuoi-trai-cay-03' => [1800000, null],
            'qua-cuoi-04' => [2200000, null],
            'mam-qua-cuoi-trai-cay-05' => [2500000, null],
        ];

        foreach ($prices as $slug => [$price, $salePrice]) {
            DB::table('products')
                ->where('slug', $slug)
                ->update([
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Data normalization is intentionally not reverted.
    }
};
