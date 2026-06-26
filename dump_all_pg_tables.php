<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$databases = ['postgres', 'pedidos', 'axstore2026', 'mapas', 'app_asistencia'];
foreach ($databases as $db) {
    config(['database.connections.pgsql.database' => $db]);
    DB::purge('pgsql');
    
    echo "========================================\n";
    echo "PGSQL DATABASE: $db\n";
    echo "========================================\n";
    
    try {
        $tables = DB::connection('pgsql')->select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public'
        ");
        
        foreach ($tables as $t) {
            $tableName = $t->table_name;
            try {
                $count = DB::connection('pgsql')->table($tableName)->count();
                if ($count > 0) {
                    echo "Table '$tableName' has $count rows.\n";
                    if (in_array($tableName, ['orders', 'archived_orders', 'loyalty_transactions'])) {
                        $rows = DB::connection('pgsql')->table($tableName)->get();
                        foreach ($rows as $row) {
                            echo "  Row in $tableName: " . json_encode($row) . "\n";
                        }
                    }
                }
            } catch (\Exception $e) {
                echo "  Error counting table '$tableName': " . $e->getMessage() . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "Error listing tables: " . $e->getMessage() . "\n";
    }
}
