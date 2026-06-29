<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderReturnRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('order_return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 40)->default('pending')->index();
            $table->string('type', 40);
            $table->string('reason', 80);
            $table->text('note')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('refund_method', 80)->nullable();
            $table->string('refund_account')->nullable();
            $table->unsignedBigInteger('refund_amount')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'requested_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_return_requests');
    }
}
