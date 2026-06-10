<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "APP_URL: " . config('app.url') . "\n";
echo "ASSET_URL: " . config('app.asset_url') . "\n";
echo "ASSET: " . asset('storage/test.jpg') . "\n";
echo "RESULT: " . str_replace(config('app.url'), config('app.asset_url', config('app.url')), asset('storage/test.jpg')) . "\n";
