<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * En PostgreSQL, cuando una columna ENUM se convierte a VARCHAR/string,
 * el CHECK constraint del ENUM original persiste en el catálogo de la BD.
 * Esta migración lo elimina para permitir cualquier valor de rol.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Eliminar el CHECK constraint heredado del ENUM original.
            // PostgreSQL nombra el constraint como "<tabla>_<columna>_check".
            DB::statement('ALTER TABLE admin_users DROP CONSTRAINT IF EXISTS admin_users_role_check');

            // Confirmar que la columna es VARCHAR sin restricciones de valores
            DB::statement("ALTER TABLE admin_users ALTER COLUMN role TYPE VARCHAR(20)");
        }

        // Para MySQL: el ENUM ya fue convertido a string por la migración anterior,
        // pero por si acaso forzamos los valores correctos.
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE admin_users MODIFY COLUMN role 
                ENUM('super_admin', 'branch_admin', 'operator', 'kitchen') 
                NOT NULL DEFAULT 'operator'");
        }
    }

    public function down(): void
    {
        // No revertimos — es una corrección de datos
    }
};
