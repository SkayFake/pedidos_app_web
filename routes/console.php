<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ──────────────────────────────────────────────────────────────────────────
// Scheduled Tasks — Cola de Trabajos (para hosting sin Supervisor)
// ──────────────────────────────────────────────────────────────────────────
//
// En un hosting como InfinityFree de paga, no hay acceso a Supervisor
// ni a un worker daemon persistente. Usamos el Scheduler de Laravel
// para ejecutar queue:work --stop-when-empty cada minuto.
//
// Este comando:
// 1. Procesa todos los jobs pendientes en la cola
// 2. Se detiene automáticamente cuando no hay más jobs
// 3. No consume recursos cuando no hay trabajo
//
// CONFIGURACIÓN DEL CRON JOB EN EL HOSTING:
// ──────────────────────────────────────────
// Comando: cd /path/to/pedidos_app && php artisan schedule:run >> /dev/null 2>&1
// Frecuencia: Cada minuto (* * * * *)
//
// Si el hosting no permite cada minuto, usar cada 5 minutos:
//   */5 * * * *  cd /path/to/pedidos_app && php artisan schedule:run >> /dev/null 2>&1
// ──────────────────────────────────────────────────────────────────────────

Schedule::command('queue:work --stop-when-empty --tries=3 --backoff=30 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));
