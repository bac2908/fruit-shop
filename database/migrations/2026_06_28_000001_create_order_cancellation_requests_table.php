<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderCancellationRequestsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('order_cancellation_requests')) {
            return;
        }

        Schema::create('order_cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 40)->default('pending')->index();
            $table->string('reason', 80);
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'requested_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_cancellation_requests');
    }
}
