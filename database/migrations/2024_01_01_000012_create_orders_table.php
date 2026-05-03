<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('deliveryman_id')->nullable()->constrained('deliverymen')->nullOnDelete();
            $table->foreignId('address_id')->constrained('customer_addresses')->restrictOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'assigned', 'on_way', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 8, 2);
            $table->decimal('delivery_fee', 8, 2);
            $table->decimal('discount_amount', 8, 2)->default(0.00);
            $table->decimal('total', 8, 2);
            $table->boolean('is_first_order_promo')->default(false);
            $table->boolean('is_free_delivery_promo')->default(false)->comment('Pedido #11 delivery gratis');
            $table->boolean('is_loyalty_discount')->default(false)->comment('5% off delivery con puntos');
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
