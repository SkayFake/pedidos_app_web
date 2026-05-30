<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('food_reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('deliveryman_reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_reviews', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::table('deliveryman_reviews', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }
};
