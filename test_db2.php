<?php
$orders = \App\Models\Order::select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->groupBy('status')->get();
foreach ($orders as $order) {
    echo $order->status . ': ' . $order->count . "\n";
}
