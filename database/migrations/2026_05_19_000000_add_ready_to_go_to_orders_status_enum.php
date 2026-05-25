<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite' || DB::getDriverName() === 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'preparing', 'ready_to_go', 'assigned', 'on_way', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite' || DB::getDriverName() === 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'preparing', 'assigned', 'on_way', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
