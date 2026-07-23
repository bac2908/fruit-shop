<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('categories', 'slogan')) {
                $table->string('slogan', 255)->nullable()->after('description');
            }
            if (! Schema::hasColumn('categories', 'icon_url')) {
                $table->string('icon_url', 1000)->nullable()->after('slogan');
            }
            if (! Schema::hasColumn('categories', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('icon_url');
            }
            if (! Schema::hasColumn('categories', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
        });

        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'placement')) {
                $table->string('placement', 40)->default('hero')->index()->after('id');
            }
            if (! Schema::hasColumn('banners', 'alt_text')) {
                $table->string('alt_text')->nullable()->after('subtitle');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'admin_role')) {
                $table->string('admin_role', 40)->nullable()->index()->after('role');
            }
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('force_password_change');
            }
            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->longText('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
            if (! Schema::hasColumn('users', 'two_factor_last_used_step')) {
                $table->unsignedBigInteger('two_factor_last_used_step')->nullable()->after('two_factor_confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = collect([
                'admin_role',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'two_factor_last_used_step',
            ])->filter(fn (string $column) => Schema::hasColumn('users', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('banners', function (Blueprint $table) {
            $columns = collect(['placement', 'alt_text'])
                ->filter(fn (string $column) => Schema::hasColumn('banners', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $columns = collect(['description', 'slogan', 'icon_url', 'meta_title', 'meta_description'])
                ->filter(fn (string $column) => Schema::hasColumn('categories', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
