<?php

namespace App\Services;

use App\Models\ArchivedOrder;
use App\Models\ArchivedOrderItem;
use App\Models\ArchivedOrderItemExtra;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de archivado de pedidos.
 * Mueve pedidos completados (delivered/cancelled) a tablas históricas
 * para mantener la tabla de pedidos activos ligera.
 */
class OrderArchivalService
{
    /**
     * Archiva un pedido completado.
     * Copia el pedido y sus relaciones a las tablas archived_*
     * y elimina los originales en una transacción atómica.
     */
    public function archiveOrder(Order $order): ArchivedOrder
    {
        if (!in_array($order->status, ['delivered', 'cancelled'])) {
            throw new \InvalidArgumentException('Solo se pueden archivar pedidos entregados o cancelados.');
        }

        return DB::transaction(function () use ($order) {
            // 1. Copiar el pedido principal
            $orderAttributes = collect($order->getAttributes())
                ->except(['id'])
                ->toArray();

            $archivedOrder = ArchivedOrder::create($orderAttributes);

            // 2. Copiar los ítems del pedido
            $order->loadMissing(['items.extras']);

            foreach ($order->items as $item) {
                $itemAttributes = collect($item->getAttributes())
                    ->except(['id'])
                    ->merge(['order_id' => $archivedOrder->id])
                    ->toArray();

                $archivedItem = ArchivedOrderItem::create($itemAttributes);

                // 3. Copiar los extras de cada ítem
                foreach ($item->extras as $extra) {
                    $extraAttributes = collect($extra->getAttributes())
                        ->except(['id'])
                        ->merge(['order_item_id' => $archivedItem->id])
                        ->toArray();

                    ArchivedOrderItemExtra::create($extraAttributes);
                }
            }

            // 4. Eliminar el pedido original (cascade eliminará items y extras)
            $order->delete();

            Log::info("Pedido #{$archivedOrder->id} archivado exitosamente.", [
                'original_id' => $order->id,
                'status' => $archivedOrder->status,
            ]);

            return $archivedOrder;
        });
    }
}
