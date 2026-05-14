<?php
declare(strict_types=1);
namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyNearbyDeliverymen implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderCreated $event): void
    {
        // Aquí iría la lógica para enviar Push (Firebase) a repartidores cercanos
        Log::info("Notificando repartidores cercanos para la orden: {$event->order->id}");
    }
}
