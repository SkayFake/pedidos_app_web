<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('card_holder'); // will be encrypted
            $table->text('card_number'); // will be encrypted
            $table->text('expiry_date'); // will be encrypted
            $table->string('card_type', 50);
            $table->string('last_four', 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_cards');
    }
};
