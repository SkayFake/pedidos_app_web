<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('milestone_id');
            $table->timestamp('achieved_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('milestone_id')->references('id')->on('reward_milestones')->onDelete('cascade');
            
            $table->unique(['user_id', 'milestone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_milestones');
    }
};
