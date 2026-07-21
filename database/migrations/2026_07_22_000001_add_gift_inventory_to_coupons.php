<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('gift_product_id')
                ->nullable()
                ->after('type')
                ->constrained('products')
                ->nullOnDelete();
            $table->unsignedSmallInteger('gift_quantity')->default(1)->after('gift_product_id');

            $table->index(['type', 'gift_product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex(['type', 'gift_product_id']);
            $table->dropConstrainedForeignId('gift_product_id');
            $table->dropColumn('gift_quantity');
        });
    }
};
