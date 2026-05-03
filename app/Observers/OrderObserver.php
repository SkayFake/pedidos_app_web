<?php

namespace App\Observers;

use App\Jobs\ProcessOrderNotification;
use App\Models\Order;
use App\Services\LoyaltyService;

class OrderObserver
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Handle the Order "created" event.
     *
     * Despacha notificación de nuevo pedido al crear.
     */
    public function created(Order $order): void
    {
        ProcessOrderNotification::dispatch($order, 'pending', 'new');
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Revisar si el estado (status) fue el que cambió
        if ($order->wasChanged('status')) {
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            // 1. Lógica síncrona (crítica, debe ser inmediata)
            $this->handleStatusChange($order);

            // 2. Notificaciones asíncronas (en cola, no bloquean la respuesta)
            ProcessOrderNotification::dispatch($order, $newStatus, $oldStatus);
        }
    }

    /**
     * Lógica síncrona de negocio al cambiar de estado.
     *
     * Esta lógica es CRÍTICA y se ejecuta de inmediato (no en cola):
     * - Puntos de lealtad
     * - Contadores de pedidos completados
     * - Contadores de repartidor
     */
    protected function handleStatusChange(Order $order): void
    {
        switch ($order->status) {
            case 'delivered':
                // 1. Ganar puntos
                $this->loyaltyService->earnPointsForOrder($order);
                
                // 2. Incrementar total de pedidos completados del cliente
                if ($order->user) {
                    $order->user->increment('total_completed_orders');
                }

                // 3. Liberar al repartidor
                if ($order->deliveryman_id && $order->deliveryman) {
                    // Evitar que baje de 0
                    if ($order->deliveryman->active_orders_count > 0) {
                        $order->deliveryman->decrement('active_orders_count');
                    }
                }
                break;

            case 'cancelled':
                // 1. Reembolsar puntos si usó descuento de lealtad
                if ($order->is_loyalty_discount) {
                    $this->loyaltyService->refundPointsForOrder($order);
                }

                // 2. Liberar al repartidor (si ya estaba asignado o en camino)
                $oldStatus = $order->getOriginal('status');
                if (in_array($oldStatus, ['assigned', 'on_way']) && $order->deliveryman_id && $order->deliveryman) {
                    if ($order->deliveryman->active_orders_count > 0) {
                        $order->deliveryman->decrement('active_orders_count');
                    }
                }
                break;

            case 'assigned':
                // Cuando se asigna un repartidor (desde un estado que no lo tenía)
                $oldStatus = $order->getOriginal('status');
                if (!in_array($oldStatus, ['assigned', 'on_way', 'delivered']) && $order->deliveryman_id && $order->deliveryman) {
                    $order->deliveryman->increment('active_orders_count');
                }
                break;
        }
    }
}
