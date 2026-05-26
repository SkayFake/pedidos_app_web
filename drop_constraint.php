<?php
use Illuminate\Support\Facades\DB;
DB::statement("ALTER TABLE orders DROP CONSTRAINT orders_status_check;");
echo "Constraint dropped successfully.\n";
