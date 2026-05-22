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
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('name', 100);
            $table->decimal('base_price', 8, 2)->default(0.00);
            $table->decimal('base_distance_km', 8, 2)->default(0.00)->comment('Radio cubierto por el precio base');
            $table->decimal('extra_per_km', 8, 2)->default(0.00)->comment('Precio extra por cada km adicional');
            $table->json('coordinates')->nullable()->comment('Polígono de la zona en JSON');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
