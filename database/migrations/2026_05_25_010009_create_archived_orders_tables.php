<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas espejo de orders, order_items y order_item_extras
 * para almacenar pedidos entregados y cancelados (historial).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── archived_orders ─────────────────────────────────────
        Schema::create('archived_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->unsignedBigInteger('deliveryman_id')->nullable();
            $table->foreignId('address_id')->constrained('customer_addresses')->restrictOnDelete();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('otp', 4)->nullable();
            $table->string('status', 20)->default('delivered');
            $table->decimal('subtotal', 8, 2);
            $table->decimal('delivery_fee', 8, 2);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->decimal('total', 8, 2);
            $table->boolean('is_first_order_promo')->default(false);
            $table->boolean('is_free_delivery_promo')->default(false);
            $table->boolean('is_loyalty_discount')->default(false);
            $table->string('cancellation_reason', 255)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('delivered_at');
        });

        // ── archived_order_items ─────────────────────────────────
        Schema::create('archived_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('archived_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedTinyInteger('quantity')->default(1);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('subtotal', 8, 2);
            $table->timestamps();
        });

        // ── archived_order_item_extras ───────────────────────────
        Schema::create('archived_order_item_extras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_item_id')->constrained('archived_order_items')->cascadeOnDelete();
            $table->unsignedBigInteger('extra_id');
            $table->unsignedTinyInteger('quantity')->default(1);
            $table->decimal('unit_price', 8, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_order_item_extras');
        Schema::dropIfExists('archived_order_items');
        Schema::dropIfExists('archived_orders');
    }
};
