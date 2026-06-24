<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVisibilityToCouponsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('coupons', 'is_public')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->boolean('is_public')->default(true)->after('is_active');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('coupons', 'is_public')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }
    }
}
