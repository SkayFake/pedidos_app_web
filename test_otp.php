<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$o = App\Models\Order::find(37);
if (!$o) {
    $o = App\Models\ArchivedOrder::find(37);
}
if (!$o) {
    echo "Order 37 not found\n";
    exit(1);
}
auth()->login($o->user);
$res = (new App\Http\Resources\V1\OrderResource($o))->toArray(request());
print_r($res);
