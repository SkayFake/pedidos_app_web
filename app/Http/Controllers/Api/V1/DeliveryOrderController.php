<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     * Pedidos Disponibles
     *
     * Retorna una lista de pedidos en estado "preparing" de la sucursal del repartidor.
     */
    public function availableOrders(): JsonResponse
    {
        $deliveryman = auth()->user();

        if (!$deliveryman->is_available) {
            return $this->error('Debes estar "Disponible" para ver los pedidos.', 403);
        }

        $query = Order::with(['user', 'address', 'branch'])
            ->where('status', 'preparing')
            ->orderBy('updated_at', 'desc');

        // Si el repartidor tiene una sucursal específica, filtramos.
        // Si su branch_id es null, significa que es "Global" y puede ver pedidos de todas las sucursales.
        if ($deliveryman->branch_id !== null) {
            $query->where('branch_id', $deliveryman->branch_id);
        }

        return $this->success([
            'orders' => $query->get()
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
        // Si es null (Global), puede tomar de cualquiera.
        if ($deliveryman->branch_id !== null && $order->branch_id !== $deliveryman->branch_id) {
            return $this->error('Este pedido pertenece a otra sucursal. Estás asignado a una sucursal diferente.', 403);
        }

        if ($order->status !== 'preparing') {
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
            'order' => $order->fresh(['user', 'address'])
        ], 'Has aceptado el pedido exitosamente.');
    }

    /**
     * Historial de Entregas y Ganancias
     */
    public function history(): JsonResponse
    {
        $deliveryman = auth()->user();

        $orders = Order::with(['user', 'address'])
            ->where('deliveryman_id', $deliveryman->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $totalEarnings = $orders->where('status', 'delivered')->sum('delivery_fee');
        
        // Calcular ganancias solo de hoy
        $todayEarnings = $orders->where('status', 'delivered')
                                ->where('delivered_at', '>=', now()->startOfDay())
                                ->sum('delivery_fee');

        return $this->success([
            'total_earnings' => round($totalEarnings, 2),
            'today_earnings' => round($todayEarnings, 2),
            'orders'         => $orders
        ]);
    }

    /**
     * Actualizar Estado del Pedido (Ej. 'on_way')
     */
    public function updateStatus(\Illuminate\Http\Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:on_way'
        ]);

        $deliveryman = auth()->user();

        if ($order->deliveryman_id !== $deliveryman->id) {
            return $this->error('No tienes permiso para actualizar este pedido.', 403);
        }

        if ($order->status !== 'assigned') {
            return $this->error('Solo puedes marcar como en camino un pedido asignado.', 422);
        }

        $order->update([
            'status' => $request->status
        ]);

        return $this->success([
            'order' => $order->fresh(['user', 'address'])
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

        return $this->success([
            'order' => $order->fresh(['user', 'address'])
        ], '¡Pedido entregado exitosamente!');
    }
}
