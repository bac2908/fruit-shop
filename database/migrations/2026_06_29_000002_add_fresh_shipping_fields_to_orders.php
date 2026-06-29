<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFreshShippingFieldsToOrders extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_fee_status')) {
                $table->string('shipping_fee_status', 40)->default('estimated')->after('shipping_fee');
            }

            if (!Schema::hasColumn('orders', 'shipping_delivery_method')) {
                $table->string('shipping_delivery_method', 60)->nullable()->after('shipping_status');
            }

            if (!Schema::hasColumn('orders', 'shipping_delivery_eta')) {
                $table->string('shipping_delivery_eta')->nullable()->after('shipping_delivery_method');
            }

            if (!Schema::hasColumn('orders', 'shipping_delivery_note')) {
                $table->text('shipping_delivery_note')->nullable()->after('shipping_delivery_eta');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_delivery_note')) {
                $table->dropColumn('shipping_delivery_note');
            }

            if (Schema::hasColumn('orders', 'shipping_delivery_eta')) {
                $table->dropColumn('shipping_delivery_eta');
            }

            if (Schema::hasColumn('orders', 'shipping_delivery_method')) {
                $table->dropColumn('shipping_delivery_method');
            }

            if (Schema::hasColumn('orders', 'shipping_fee_status')) {
                $table->dropColumn('shipping_fee_status');
            }
        });
    }
}
