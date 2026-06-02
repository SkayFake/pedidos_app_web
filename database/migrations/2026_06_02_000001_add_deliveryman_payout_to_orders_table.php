<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('deliveryman_payout', 8, 2)->default(0)->after('delivery_fee');
        });
        
        Schema::table('archived_orders', function (Blueprint $table) {
            $table->decimal('deliveryman_payout', 8, 2)->default(0)->after('delivery_fee');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('deliveryman_payout');
        });
        
        Schema::table('archived_orders', function (Blueprint $table) {
            $table->dropColumn('deliveryman_payout');
        });
    }
};
