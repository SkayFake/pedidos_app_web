<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$products = \App\Models\Product::take(1)->get();
$resource = \App\Http\Resources\V1\ProductResource::collection($products);
echo json_encode($resource);
