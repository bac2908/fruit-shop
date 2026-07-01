<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchHistoriesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('search_histories')) {
            return;
        }

        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('keyword', 150);
            $table->string('keyword_normalized', 150);
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'keyword_normalized']);
            $table->index(['user_id', 'last_searched_at']);
            $table->index('keyword_normalized');
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_histories');
    }
}
