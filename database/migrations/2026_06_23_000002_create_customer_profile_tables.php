<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerProfileTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'birthday')) {
                    $table->date('birthday')->nullable()->after('address');
                }

                if (!Schema::hasColumn('users', 'gender')) {
                    $table->string('gender', 20)->nullable()->after('birthday');
                }

                if (!Schema::hasColumn('users', 'avatar_url')) {
                    $table->string('avatar_url')->nullable()->after('gender');
                }

                if (!Schema::hasColumn('users', 'notify_order_status')) {
                    $table->boolean('notify_order_status')->default(true)->after('avatar_url');
                }

                if (!Schema::hasColumn('users', 'notify_promotions')) {
                    $table->boolean('notify_promotions')->default(false)->after('notify_order_status');
                }
            });
        }

        if (!Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('recipient_name', 120);
                $table->string('phone', 20);
                $table->string('address_line');
                $table->string('ward', 120)->nullable();
                $table->string('district', 120)->nullable();
                $table->string('province', 120)->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['user_id', 'is_default']);
            });
        }

        if (!Schema::hasTable('wishlist_items')) {
            Schema::create('wishlist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('product_views')) {
            Schema::create('product_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('view_count')->default(1);
                $table->timestamp('last_viewed_at')->useCurrent()->index();
                $table->timestamps();

                $table->unique(['user_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('user_vouchers')) {
            Schema::create('user_vouchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamp('used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'coupon_id']);
                $table->index(['user_id', 'used_at', 'expires_at']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('user_vouchers');
        Schema::dropIfExists('product_views');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('user_addresses');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columns = [
                    'birthday',
                    'gender',
                    'avatar_url',
                    'notify_order_status',
                    'notify_promotions',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}
