<?php
declare(strict_types=1);
namespace App\Observers;

use App\Models\Zone;
use Illuminate\Support\Facades\Cache;

class ZoneObserver
{
    public function saved(Zone $zone): void
    {
        Cache::forget('api.zones.active');
    }

    public function deleted(Zone $zone): void
    {
        Cache::forget('api.zones.active');
    }
}
