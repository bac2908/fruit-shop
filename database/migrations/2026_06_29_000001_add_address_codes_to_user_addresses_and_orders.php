<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressCodesToUserAddressesAndOrders extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_addresses')) {
            $hasProvinceCode = Schema::hasColumn('user_addresses', 'province_code');
            $hasWardCode = Schema::hasColumn('user_addresses', 'ward_code');

            Schema::table('user_addresses', function (Blueprint $table) use ($hasProvinceCode, $hasWardCode) {
                if (!$hasProvinceCode) {
                    $table->string('province_code', 20)->nullable()->after('province');
                }

                if (!$hasWardCode) {
                    $table->string('ward_code', 20)->nullable()->after('province_code');
                }

                if (!$hasProvinceCode || !$hasWardCode) {
                    $table->index(['province_code', 'ward_code'], 'user_addresses_province_ward_index');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'shipping_province_code')) {
                    $table->string('shipping_province_code', 20)->nullable()->after('shipping_address');
                }

                if (!Schema::hasColumn('orders', 'shipping_ward_code')) {
                    $table->string('shipping_ward_code', 20)->nullable()->after('shipping_province_code');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'shipping_ward_code')) {
                    $table->dropColumn('shipping_ward_code');
                }

                if (Schema::hasColumn('orders', 'shipping_province_code')) {
                    $table->dropColumn('shipping_province_code');
                }
            });
        }

        if (Schema::hasTable('user_addresses')) {
            try {
                Schema::table('user_addresses', function (Blueprint $table) {
                    $table->dropIndex('user_addresses_province_ward_index');
                });
            } catch (Throwable $exception) {
                //
            }

            Schema::table('user_addresses', function (Blueprint $table) {
                if (Schema::hasColumn('user_addresses', 'ward_code')) {
                    $table->dropColumn('ward_code');
                }

                if (Schema::hasColumn('user_addresses', 'province_code')) {
                    $table->dropColumn('province_code');
                }
            });
        }
    }
}
