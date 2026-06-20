<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->string('provider_token')->nullable()->after('last_four');
            $table->dropColumn(['card_holder', 'card_number', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->dropColumn('provider_token');
            $table->text('card_holder')->nullable();
            $table->text('card_number')->nullable();
            $table->text('expiry_date')->nullable();
        });
    }
};
