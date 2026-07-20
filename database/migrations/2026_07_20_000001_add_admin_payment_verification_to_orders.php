<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference', 120)->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('orders', 'payment_verified_by')) {
                $table->foreignId('payment_verified_by')
                    ->nullable()
                    ->after('payment_reference')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'payment_verified_at')) {
                $table->timestamp('payment_verified_at')->nullable()->after('payment_verified_by');
            }

            if (! Schema::hasColumn('orders', 'refund_reference')) {
                $table->string('refund_reference', 120)->nullable()->after('payment_verified_at');
            }

            if (! Schema::hasColumn('orders', 'refunded_by')) {
                $table->foreignId('refunded_by')
                    ->nullable()
                    ->after('refund_reference')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refunded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_verified_by')) {
                $table->dropConstrainedForeignId('payment_verified_by');
            }

            if (Schema::hasColumn('orders', 'refunded_by')) {
                $table->dropConstrainedForeignId('refunded_by');
            }

            $columns = collect(['payment_reference', 'payment_verified_at', 'refund_reference', 'refunded_at'])
                ->filter(fn (string $column) => Schema::hasColumn('orders', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
