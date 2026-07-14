<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('subject', 160)->nullable()->after('phone');
            $table->foreignId('handled_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable()->after('handled_by');
            $table->text('reply_message')->nullable()->after('admin_note');
            $table->timestamp('read_at')->nullable()->after('reply_message');
            $table->timestamp('replied_at')->nullable()->after('read_at');
            $table->char('fingerprint', 64)->nullable()->after('replied_at')->index();
            $table->unsignedTinyInteger('spam_score')->default(0)->after('fingerprint')->index();
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('handled_by');
            $table->dropIndex(['fingerprint']);
            $table->dropIndex(['spam_score']);
            $table->dropColumn([
                'subject',
                'admin_note',
                'reply_message',
                'read_at',
                'replied_at',
                'fingerprint',
                'spam_score',
            ]);
        });
    }
};
