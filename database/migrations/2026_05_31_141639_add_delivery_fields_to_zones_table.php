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
        Schema::table('zones', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->decimal('base_distance_km', 8, 2)->default(0.00)->comment('Radio cubierto por la tarifa de envío base');
            $table->decimal('extra_per_km', 8, 2)->default(0.00)->comment('Cobro extra por kilómetro adicional');
            $table->boolean('allow_out_of_zone_delivery')->default(false)->comment('Permitir envíos fuera del radio base con costo por km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'base_distance_km', 'extra_per_km', 'allow_out_of_zone_delivery']);
        });
    }
};
