<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
        });

        Schema::table('deliverymen', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('deliverymen', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
