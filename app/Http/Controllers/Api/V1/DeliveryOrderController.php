<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group Pedidos del Repartidor
 *
 * Endpoints para listar pedidos disponibles y aceptarlos.
 */
class DeliveryOrderController extends Controller
{
    use ApiResponse;

    /**
     * Pedidos Activos (En curso)
     *
     * Retorna los pedidos que el repartidor ya aceptó y están pendientes de entregar
     * (estados: assigned, on_way).
     */
    public function activeOrders(): JsonResponse
    {
        $deliveryman = auth()->user();

        $query = Order::with(['user', 'address', 'branch'])
            ->where('deliveryman_id', $deliveryman->id)
            ->whereIn('status', ['assigned', 'on_way'])
            ->orderBy('updated_at', 'desc');

        return $this->success([
            'orders' => OrderResource::collection($query->get())
        ]);
    }

    /**
     * Pedidos Disponibles
     *
     * Retorna una lista de pedidos en estado "ready_to_go" (Listo para enviar) de la sucursal del repartidor.
     */
    public function availableOrders(): JsonResponse
    {
        $deliveryman = auth()->user();

        if (!$deliveryman->is_available) {
            return $this->error('Debes estar "Disponible" para ver los pedidos.', 403);
        }

        $query = Order::with(['user', 'address', 'branch'])
            ->where('status', 'ready_to_go')
            ->whereNull('deliveryman_id')
            ->orderBy('updated_at', 'desc');

        // Si el repartidor tiene una sucursal específica, filtramos.
        // Si su branch_id es null o 0, significa que es "Global" y puede ver pedidos de todas las sucursales.
        if (!empty($deliveryman->branch_id) && $deliveryman->branch_id > 0) {
            $query->where('branch_id', $deliveryman->branch_id);
        }

        return $this->success([
            'orders' => OrderResource::collection($query->get())
        ]);
    }

    /**
     * Aceptar Pedido
     *
     * Permite al repartidor aceptar un pedido específico.
     */
    public function acceptOrder(Order $order): JsonResponse
    {
        $deliveryman = auth()->user();

        if (!$deliveryman->is_available) {
            return $this->error('Debes estar "Disponible" para aceptar pedidos.', 403);
        }

        // Si el repartidor tiene sucursal asignada, no puede tomar pedidos de otras sucursales.
        // Si es null o 0 (Global), puede tomar de cualquiera.
        if (!empty($deliveryman->branch_id) && $deliveryman->branch_id > 0 && $order->branch_id !== $deliveryman->branch_id) {
            return $this->error('Este pedido pertenece a otra sucursal. Estás asignado a una sucursal diferente.', 403);
        }

        if ($order->status !== 'ready_to_go') {
            return $this->error('El pedido ya no está disponible para ser asignado.', 422);
        }

        if ($order->deliveryman_id) {
            return $this->error('El pedido ya ha sido tomado por otro repartidor.', 422);
        }

        DB::transaction(function () use ($order, $deliveryman) {
            $order->update([
                'status'         => 'assigned',
                'deliveryman_id' => $deliveryman->id,
                'assigned_at'    => now(),
            ]);
        });

        return $this->success([
            'order' => new OrderResource($order->fresh(['user', 'address', 'branch']))
        ], 'Has aceptado el pedido exitosamente.');
    }

    /**
     * Historial de Entregas y Ganancias
     */
    public function history(): JsonResponse
    {
        $deliveryman = auth()->user();

        $orders = \App\Models\ArchivedOrder::with(['user', 'address', 'branch', 'items.product'])
            ->where('deliveryman_id', $deliveryman->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $totalEarnings = $orders->where('status', 'delivered')->sum('deliveryman_payout');

        // Ganancias de hoy: usar delivered_at si existe, sino created_at
        $todayStart = now()->startOfDay();
        $todayEarnings = $orders->where('status', 'delivered')
            ->filter(function ($o) use ($todayStart) {
                $date = $o->delivered_at ?? $o->created_at;
                return $date && $date->gte($todayStart);
            })
            ->sum('deliveryman_payout');

        return $this->success([
            'total_earnings' => round($totalEarnings, 2),
            'today_earnings' => round($todayEarnings, 2),
            'orders'         => OrderResource::collection($orders)
        ]);
    }

    public function updateStatus(\Illuminate\Http\Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:on_way'
        ]);

        $deliveryman = auth()->user();

        if ($order->deliveryman_id !== $deliveryman->id) {
            return $this->error('No tienes permiso para actualizar este pedido.', 403);
        }

        if ($order->status === 'on_way') {
            return $this->success([
                'order' => new OrderResource($order->fresh(['user', 'address', 'branch']))
            ], 'El pedido ya está en camino.');
        }

        if ($order->status !== 'assigned') {
            return $this->error('Solo puedes marcar como en camino un pedido asignado.', 422);
        }

        $order->update([
            'status' => $request->status
        ]);

        return $this->success([
            'order' => new OrderResource($order->fresh(['user', 'address', 'branch']))
        ], 'Estado del pedido actualizado exitosamente.');
    }

    /**
     * Verificar Código OTP de Entrega
     */
    public function verifyOtp(\Illuminate\Http\Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'otp_code' => 'required|string|size:4'
        ]);

        $deliveryman = auth()->user();

        if ($order->deliveryman_id !== $deliveryman->id) {
            return $this->error('No tienes permiso para actualizar este pedido.', 403);
        }

        if (!in_array($order->status, ['assigned', 'on_way'])) {
            return $this->error('El pedido debe estar en camino para poder entregarlo.', 422);
        }

        if ($order->otp !== $request->otp_code) {
            return $this->error('El código proporcionado por el cliente es inválido.', 422);
        }

        $order->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);

        $freshOrder = $order->fresh(['user', 'address', 'branch']);
        if (!$freshOrder) {
            $freshOrder = \App\Models\ArchivedOrder::with(['user', 'address', 'branch'])
                ->where('user_id', $order->user_id)
                ->where('created_at', $order->created_at)
                ->first();
        }

        return $this->success([
            'order' => new OrderResource($freshOrder)
        ], '¡Pedido entregado exitosamente!');
    }
}
