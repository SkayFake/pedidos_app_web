<?php
declare(strict_types=1);
namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function saved(Product $product): void
    {
        Cache::increment('products_cache_version');
    }

    public function deleted(Product $product): void
    {
        Cache::increment('products_cache_version');
    }
}
