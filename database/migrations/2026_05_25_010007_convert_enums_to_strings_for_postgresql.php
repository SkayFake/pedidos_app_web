<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte columnas ENUM a STRING para compatibilidad con PostgreSQL
 * y agrega el rol 'kitchen' al campo role de admin_users.
 */
return new class extends Migration
{
    public function up(): void
    {
        // admin_users.role → string (agrega 'kitchen' como valor válido)
        Schema::table('admin_users', function (Blueprint $table) {
            $table->string('role', 20)->default('operator')->change();
        });

        // deliverymen.vehicle_type → string
        Schema::table('deliverymen', function (Blueprint $table) {
            $table->string('vehicle_type', 20)->default('motorcycle')->change();
        });

        // orders.status → string
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });

        // coupons.type → string
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });

        // loyalty_transactions.type → string
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });
    }

    public function down(): void
    {
        // No revertimos a ENUM para evitar pérdida de datos
    }
};
