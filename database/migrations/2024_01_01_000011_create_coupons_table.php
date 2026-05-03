<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->enum('type', ['percent', 'fixed', 'free_delivery']);
            $table->decimal('value', 8, 2)->default(0.00)->comment('% o monto fijo. 0 si es free_delivery.');
            $table->decimal('min_order_amount', 8, 2)->default(0.00);
            $table->unsignedInteger('max_uses_total')->nullable()->comment('NULL = sin límite global');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
