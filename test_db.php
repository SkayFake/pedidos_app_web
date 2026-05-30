<?php
echo \App\Models\Order::count() . '|' . \App\Models\ArchivedOrder::count() . '|' . \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
