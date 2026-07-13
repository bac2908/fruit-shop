<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMomoIntegrityFieldsToOrders extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('momo_request_id', 100)->nullable()->unique()->after('payment_status');
            $table->string('momo_transaction_id', 100)->nullable()->unique()->after('momo_request_id');
            $table->timestamp('payment_expires_at')->nullable()->index()->after('momo_transaction_id');
        });

        DB::table('orders')
            ->where('payment_method', 'momo')
            ->whereNull('momo_request_id')
            ->orderBy('id')
            ->get(['id', 'code', 'created_at'])
            ->each(function ($order) {
                DB::table('orders')->where('id', $order->id)->update([
                    'momo_request_id' => $order->code,
                    'payment_expires_at' => $order->created_at
                        ? Carbon::parse($order->created_at)->addMinutes(30)
                        : now()->addMinutes(30),
                ]);
            });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['momo_request_id']);
            $table->dropUnique(['momo_transaction_id']);
            $table->dropIndex(['payment_expires_at']);
            $table->dropColumn(['momo_request_id', 'momo_transaction_id', 'payment_expires_at']);
        });
    }
}
