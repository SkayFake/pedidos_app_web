<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('points_required');
            $table->unsignedBigInteger('coupon_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_milestones');
    }
};
