<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrepareBackendSchema extends Migration
{
    public function up()
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'sku')) {
                    $table->string('sku')->nullable()->unique();
                }

                if (!Schema::hasColumn('products', 'cost_price')) {
                    $table->unsignedBigInteger('cost_price')->nullable();
                }

                if (!Schema::hasColumn('products', 'low_stock_threshold')) {
                    $table->unsignedInteger('low_stock_threshold')->default(0);
                }

                if (!Schema::hasColumn('products', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0);
                }

                if (!Schema::hasColumn('products', 'meta_title')) {
                    $table->string('meta_title')->nullable();
                }

                if (!Schema::hasColumn('products', 'meta_description')) {
                    $table->text('meta_description')->nullable();
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'public_token')) {
                    $table->string('public_token', 80)->nullable()->unique();
                }

                if (!Schema::hasColumn('orders', 'customer_note')) {
                    $table->text('customer_note')->nullable();
                }

                if (!Schema::hasColumn('orders', 'admin_note')) {
                    $table->text('admin_note')->nullable();
                }

                if (!Schema::hasColumn('orders', 'payment_method')) {
                    $table->string('payment_method', 40)->default('cod');
                }

                if (!Schema::hasColumn('orders', 'payment_status')) {
                    $table->string('payment_status', 40)->default('unpaid')->index();
                }

                if (!Schema::hasColumn('orders', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable();
                }

                if (!Schema::hasColumn('orders', 'shipping_status')) {
                    $table->string('shipping_status', 40)->default('pending')->index();
                }

                if (!Schema::hasColumn('orders', 'shipping_provider')) {
                    $table->string('shipping_provider')->nullable();
                }

                if (!Schema::hasColumn('orders', 'tracking_code')) {
                    $table->string('tracking_code')->nullable();
                }

                if (!Schema::hasColumn('orders', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                if (!Schema::hasColumn('coupons', 'usage_limit')) {
                    $table->unsignedInteger('usage_limit')->nullable();
                }

                if (!Schema::hasColumn('coupons', 'per_customer_limit')) {
                    $table->unsignedInteger('per_customer_limit')->nullable();
                }

                if (!Schema::hasColumn('coupons', 'used_count')) {
                    $table->unsignedInteger('used_count')->default(0);
                }

                if (!Schema::hasColumn('coupons', 'max_discount')) {
                    $table->unsignedBigInteger('max_discount')->nullable();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'password_changed_at')) {
                    $table->timestamp('password_changed_at')->nullable();
                }

                if (!Schema::hasColumn('users', 'force_password_change')) {
                    $table->boolean('force_password_change')->default(false);
                }

                if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                    $table->unsignedInteger('failed_login_attempts')->default(0);
                }

                if (!Schema::hasColumn('users', 'locked_until')) {
                    $table->timestamp('locked_until')->nullable();
                }
            });
        }

        if (!Schema::hasTable('order_status_histories')) {
            Schema::create('order_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('previous_status', 40)->nullable();
                $table->string('status', 40);
                $table->text('note')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['order_id', 'created_at']);
                $table->index('status');
            });
        }

        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('coupon_code', 80);
                $table->string('customer_email')->nullable();
                $table->unsignedBigInteger('discount_total')->default(0);
                $table->timestamp('used_at')->useCurrent();
                $table->timestamps();

                $table->unique(['coupon_id', 'order_id']);
                $table->index(['coupon_code', 'used_at']);
                $table->index('customer_email');
            });
        }

        if (!Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 40);
                $table->integer('quantity');
                $table->integer('stock_before')->default(0);
                $table->integer('stock_after')->default(0);
                $table->unsignedBigInteger('unit_cost')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'created_at']);
                $table->index('type');
            });
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type', 40)->default('string');
                $table->string('group', 80)->default('general')->index();
                $table->boolean('is_public')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('image_url');
                $table->string('link_url')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('password_history')) {
            Schema::create('password_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('password_hash');
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('secure_sessions')) {
            Schema::create('secure_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('session_id', 120)->unique();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('security_audit_log')) {
            Schema::create('security_audit_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 120);
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['action', 'created_at']);
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('failed_login_attempts')) {
            Schema::create('failed_login_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('email')->nullable()->index();
                $table->string('ip_address', 45)->nullable()->index();
                $table->text('user_agent')->nullable();
                $table->timestamp('attempt_time')->useCurrent()->index();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('failed_login_attempts');
        Schema::dropIfExists('security_audit_log');
        Schema::dropIfExists('secure_sessions');
        Schema::dropIfExists('password_history');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('order_status_histories');

        $this->dropExistingColumns('users', [
            'password_changed_at',
            'force_password_change',
            'failed_login_attempts',
            'locked_until',
        ]);

        $this->dropExistingColumns('coupons', [
            'usage_limit',
            'per_customer_limit',
            'used_count',
            'max_discount',
        ]);

        $this->dropExistingColumns('orders', [
            'public_token',
            'customer_note',
            'admin_note',
            'payment_method',
            'payment_status',
            'paid_at',
            'shipping_status',
            'shipping_provider',
            'tracking_code',
            'cancelled_at',
        ]);

        $this->dropExistingColumns('products', [
            'sku',
            'cost_price',
            'low_stock_threshold',
            'sort_order',
            'meta_title',
            'meta_description',
        ]);
    }

    private function dropExistingColumns(string $tableName, array $columns): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $existingColumns = array_values(array_filter($columns, function ($column) use ($tableName) {
            return Schema::hasColumn($tableName, $column);
        }));

        if (empty($existingColumns)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }
}
