<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliverymen', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 20)->unique();
            $table->string('password', 255);
            $table->enum('vehicle_type', ['motorcycle', 'bicycle', 'car'])->default('motorcycle');
            $table->string('license_plate', 20)->nullable();
            $table->boolean('is_available')->default(true)->comment('Disponible para recibir pedidos');
            $table->boolean('is_active')->default(true)->comment('Habilitado por el admin');
            $table->unsignedTinyInteger('active_orders_count')->default(0)->comment('Máximo 3 simultáneos');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverymen');
    }
};
