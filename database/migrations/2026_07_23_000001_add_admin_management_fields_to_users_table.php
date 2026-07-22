<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status', 24)->default('active')->index()->after('role');
            $table->timestamp('suspended_at')->nullable()->after('account_status');
            $table->foreignId('suspended_by')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();
            $table->text('suspension_reason')->nullable()->after('suspended_by');
            $table->text('admin_note')->nullable()->after('suspension_reason');
            $table->unsignedInteger('session_version')->default(1)->after('admin_note');
            $table->timestamp('last_login_at')->nullable()->index()->after('session_version');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['suspended_by']);
            $table->dropIndex(['account_status']);
            $table->dropIndex(['last_login_at']);
            $table->dropColumn([
                'account_status',
                'suspended_at',
                'suspended_by',
                'suspension_reason',
                'admin_note',
                'session_version',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
