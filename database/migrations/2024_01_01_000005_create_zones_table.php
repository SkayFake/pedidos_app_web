<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Ej: Ciudad Capital, Mixco');
            $table->string('city', 100);
            $table->decimal('delivery_fee', 8, 2)->default(0.00);
            $table->boolean('is_deliverable')->default(true)->comment('FALSE = cantones sin cobertura');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
