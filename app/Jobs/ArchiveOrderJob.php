<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderArchivalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para archivar pedidos completados de forma asíncrona.
 * Se ejecuta después de que un pedido cambia a delivered/cancelled.
 */
class ArchiveOrderJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $orderId,
    ) {}

    public function handle(OrderArchivalService $archivalService): void
    {
        $order = Order::with(['items.extras'])->find($this->orderId);

        if (!$order) {
            Log::info("ArchiveOrderJob: Pedido #{$this->orderId} ya no existe (probablemente ya archivado).");
            return;
        }

        if (!in_array($order->status, ['delivered', 'cancelled'])) {
            Log::warning("ArchiveOrderJob: Pedido #{$this->orderId} no está en estado archivable.", [
                'status' => $order->status,
            ]);
            return;
        }

        try {
            $archivalService->archiveOrder($order);
        } catch (\Throwable $e) {
            Log::error("ArchiveOrderJob: Error al archivar pedido #{$this->orderId}", [
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-lanzar para reintentos
        }
    }
}
