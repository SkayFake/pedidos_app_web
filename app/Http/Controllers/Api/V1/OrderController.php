<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OrderValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CancelOrderRequest;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Pedidos
 *
 * Endpoints para crear, consultar y cancelar pedidos del cliente autenticado.
 *
 * **Seguridad**: Al crear un pedido, el backend calcula todos los montos server-side.
 * Los precios se leen directamente de la base de datos (products.base_price +
 * product_variants.price_modifier + product_extras.price). El cliente solo envía
 * IDs y cantidades.
 */
class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * Listar mis pedidos
     *
     * Retorna los pedidos del cliente autenticado, paginados y ordenados por más reciente.
     * Opcionalmente se puede filtrar por estado.
     *
     * @queryParam status string Filtrar por estado del pedido. Valores: pending, confirmed, preparing, assigned, on_way, delivered, cancelled. Example: pending
     * @queryParam per_page integer Cantidad por página (máx 50). Example: 10
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 15,
     *       "status": "pending",
     *       "subtotal": "12.50",
     *       "subtotal_fmt": "$12.50",
     *       "delivery_fee": "2.00",
     *       "delivery_fee_fmt": "$2.00",
     *       "discount_amount": "0.00",
     *       "discount_amount_fmt": "$0.00",
     *       "total": "14.50",
     *       "total_fmt": "$14.50",
     *       "branch": { "id": 1, "name": "Sucursal Centro", "address": "Calle Principal #123" },
     *       "items": [
     *         {
     *           "id": 20,
     *           "product_name": "Hamburguesa Clásica",
     *           "variant_name": null,
     *           "quantity": 2,
     *           "unit_price": "5.50",
     *           "unit_price_fmt": "$5.50",
     *           "subtotal": "11.00",
     *           "subtotal_fmt": "$11.00"
     *         }
     *       ],
     *       "is_first_order_promo": false,
     *       "is_free_delivery_promo": false,
     *       "is_loyalty_discount": false,
     *       "notes": null,
     *       "created_at": "02 May 2026, 10:30 PM"
     *     }
     *   ],
     *   "links": { "first": "...", "last": "...", "prev": null, "next": null },
     *   "meta": { "current_page": 1, "last_page": 1, "per_page": 10, "total": 3 }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = auth()->user()->orders()
            ->with(['items.product', 'branch'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = min($request->integer('per_page', 10), 50);

        return OrderResource::collection($query->paginate($perPage));
    }

    /**
     * Detalle de pedido
     *
     * Retorna la información completa de un pedido, incluyendo ítems con extras,
     * datos de la sucursal, dirección y cupón aplicado. Solo el dueño puede consultarlo.
     *
     * @urlParam order integer required ID del pedido. Example: 15
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Detalle del pedido.",
     *   "data": {
     *     "id": 15,
     *     "status": "delivered",
     *     "subtotal": "12.50",
     *     "subtotal_fmt": "$12.50",
     *     "delivery_fee": "0.00",
     *     "delivery_fee_fmt": "$0.00",
     *     "discount_amount": "1.25",
     *     "discount_amount_fmt": "$1.25",
     *     "total": "11.25",
     *     "total_fmt": "$11.25",
     *     "branch": { "id": 1, "name": "Sucursal Centro", "address": "Calle Principal #123" },
     *     "address": { "id": 3, "label": "Casa", "street": "Col. Escalón, Calle 5 #42" },
     *     "items": [
     *       {
     *         "id": 20,
     *         "product_name": "Hamburguesa Clásica",
     *         "variant_name": "Tamaño Grande",
     *         "quantity": 1,
     *         "unit_price": "7.50",
     *         "unit_price_fmt": "$7.50",
     *         "subtotal": "8.25",
     *         "subtotal_fmt": "$8.25",
     *         "extras": [
     *           { "id": 5, "extra_name": "Queso Extra", "quantity": 1, "unit_price": "0.75", "unit_price_fmt": "$0.75" }
     *         ]
     *       }
     *     ],
     *     "coupon_code": "BIENVENIDO10",
     *     "is_first_order_promo": true,
     *     "is_free_delivery_promo": false,
     *     "is_loyalty_discount": false,
     *     "notes": "Sin cebolla por favor",
     *     "confirmed_at": "02 May 2026, 10:32 PM",
     *     "delivered_at": "02 May 2026, 11:15 PM",
     *     "created_at": "02 May 2026, 10:30 PM"
     *   }
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "No tienes permiso para ver este pedido."
     * }
     */
    public function show(Order $order): JsonResponse
    {
        // Policy: solo el dueño puede ver su pedido
        if ($order->user_id !== auth()->id()) {
            return $this->error('No tienes permiso para ver este pedido.', 403);
        }

        $order->load([
            'branch',
            'address',
            'coupon',
            'items.product',
            'items.variant',
            'items.extras.extra',
        ]);

        return $this->success(
            new OrderResource($order),
            'Detalle del pedido.'
        );
    }

    /**
     * Crear pedido
     *
     * Crea un nuevo pedido con cálculo seguro del total server-side.
     *
     * **IMPORTANTE**: El backend JAMÁS confía en montos enviados por el cliente.
     * Solo se aceptan IDs de productos, variantes, extras y sus cantidades.
     * Los precios se calculan leyendo la base de datos.
     *
     * **Flujo del cálculo**:
     * 1. `products.base_price` → precio base
     * 2. `+ product_variants.price_modifier` → ajuste de variante
     * 3. `+ product_extras.price × cantidad` → extras
     * 4. Aplica promociones (1er pedido, #11, lealtad)
     * 5. Aplica cupón si es válido
     * 6. Delivery fee fijo: $2.00
     *
     * @bodyParam branch_id integer required ID de la sucursal. Example: 1
     * @bodyParam address_id integer required ID de la dirección de entrega del cliente. Example: 3
     * @bodyParam coupon_code string Código de cupón a aplicar. Example: BIENVENIDO10
     * @bodyParam use_loyalty_points boolean Usar puntos de lealtad para descuento. Example: false
     * @bodyParam notes string Instrucciones especiales (máx 500 caracteres). Example: Sin cebolla por favor
     * @bodyParam items object[] required Lista de productos a pedir.
     * @bodyParam items[].product_id integer required ID del producto. Example: 1
     * @bodyParam items[].variant_id integer ID de la variante (opcional). Example: 2
     * @bodyParam items[].quantity integer required Cantidad (1-20). Example: 2
     * @bodyParam items[].extras object[] Extras para este ítem.
     * @bodyParam items[].extras[].extra_id integer required ID del extra. Example: 1
     * @bodyParam items[].extras[].quantity integer required Cantidad del extra (1-5). Example: 1
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Pedido creado exitosamente.",
     *   "data": {
     *     "id": 16,
     *     "status": "pending",
     *     "subtotal": "12.50",
     *     "subtotal_fmt": "$12.50",
     *     "delivery_fee": "2.00",
     *     "delivery_fee_fmt": "$2.00",
     *     "discount_amount": "0.00",
     *     "discount_amount_fmt": "$0.00",
     *     "total": "14.50",
     *     "total_fmt": "$14.50",
     *     "notes": "Sin cebolla por favor",
     *     "created_at": "02 May 2026, 11:30 PM"
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "El producto \"Hamburguesa Especial\" no está disponible."
     * }
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            $order = $this->orderService->createOrder($user, $request->validated());
        } catch (OrderValidationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            report($e);
            return $this->error('Ocurrió un error al procesar tu pedido. Intenta de nuevo.', 500);
        }

        // Cargar relaciones para la respuesta
        $order->load([
            'branch',
            'address',
            'items.product',
            'items.variant',
            'items.extras.extra',
        ]);

        return $this->success(
            new OrderResource($order),
            'Pedido creado exitosamente.',
            201
        );
    }

    /**
     * Cancelar pedido
     *
     * Cancela un pedido existente. Solo se puede cancelar si el estado es `pending` o `confirmed`.
     * Una vez que el pedido está en preparación o posterior, no se puede cancelar desde la app.
     *
     * @urlParam order integer required ID del pedido a cancelar. Example: 16
     *
     * @bodyParam cancellation_reason string Razón de la cancelación (máx 255 caracteres). Example: Cambié de opinión
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Pedido cancelado exitosamente.",
     *   "data": {
     *     "id": 16,
     *     "status": "cancelled",
     *     "cancellation_reason": "Cambié de opinión",
     *     "cancelled_at": "02 May 2026, 11:35 PM"
     *   }
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "No tienes permiso para cancelar este pedido."
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Este pedido no puede ser cancelado porque ya está en preparación o fue entregado."
     * }
     */
    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        // Policy: solo el dueño puede cancelar
        if ($order->user_id !== auth()->id()) {
            return $this->error('No tienes permiso para cancelar este pedido.', 403);
        }

        // Solo se puede cancelar si aún no se está preparando
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return $this->error(
                'Este pedido no puede ser cancelado porque ya está en preparación o fue entregado.',
                422
            );
        }

        $order->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason ?? 'Cancelado por el cliente',
            'cancelled_at'        => now(),
        ]);

        $order->load(['branch', 'items.product']);

        return $this->success(
            new OrderResource($order),
            'Pedido cancelado exitosamente.'
        );
    }
}
