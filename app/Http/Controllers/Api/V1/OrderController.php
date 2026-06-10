<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OrderValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CancelOrderRequest;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Models\ArchivedOrder;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * Retorna los pedidos del cliente autenticado (activos + archivados),
     * paginados y ordenados por más reciente.
     * Opcionalmente se puede filtrar por estado.
     *
     * @queryParam status string Filtrar por estado del pedido. Valores: pending, confirmed, preparing, ready_to_go, assigned, on_way, delivered, cancelled. Example: pending
     * @queryParam per_page integer Cantidad por página (máx 50). Example: 10
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = auth()->user();
        $perPage = min($request->integer('per_page', 10), 50);
        $statusFilter = $request->filled('status') ? $request->string('status')->toString() : null;

        // Estados activos vs archivados
        $activeStatuses = ['pending', 'confirmed', 'preparing', 'ready_to_go', 'assigned', 'on_way'];
        $archivedStatuses = ['delivered', 'cancelled'];

        $shouldQueryActive = !$statusFilter || in_array($statusFilter, $activeStatuses);
        $shouldQueryArchived = !$statusFilter || in_array($statusFilter, $archivedStatuses);

        // Si el filtro apunta solo a una tabla, simplificamos
        if ($statusFilter && !$shouldQueryActive) {
            // Solo archivados
            $query = ArchivedOrder::where('user_id', $user->id)
                ->with(['items.product', 'items.variant', 'items.extras.extra', 'branch', 'deliveryman', 'address.zone'])
                ->where('status', $statusFilter)
                ->latest();

            return OrderResource::collection($query->paginate($perPage));
        }

        if ($statusFilter && !$shouldQueryArchived) {
            // Solo activos
            $query = $user->orders()
                ->with(['items.product', 'items.variant', 'items.extras.extra', 'branch', 'deliveryman', 'address.zone'])
                ->where('status', $statusFilter)
                ->latest();

            return OrderResource::collection($query->paginate($perPage));
        }

        // Sin filtro o filtro ambiguo: unificar ambas tablas
        $activeOrders = $user->orders()
            ->with(['items.product', 'items.variant', 'items.extras.extra', 'branch', 'deliveryman', 'address.zone'])
            ->get();

        $archivedOrders = ArchivedOrder::where('user_id', $user->id)
            ->with(['items.product', 'items.variant', 'items.extras.extra', 'branch', 'deliveryman', 'address.zone'])
            ->get();

        $allOrders = $activeOrders->merge($archivedOrders)
            ->sortByDesc('created_at')
            ->values();

        // Paginación manual
        $page = $request->integer('page', 1);
        $slice = $allOrders->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $allOrders->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return OrderResource::collection($paginator);
    }

    /**
     * Detalle de pedido
     *
     * Retorna la información completa de un pedido.
     * Busca tanto en pedidos activos como en el archivo histórico.
     * Solo el dueño puede consultarlo.
     *
     * @urlParam id integer required ID del pedido. Example: 15
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        // Buscar en pedidos activos
        $order = Order::with([
            'branch', 'address.zone', 'coupon', 'deliveryman',
            'items.product', 'items.variant', 'items.extras.extra',
        ])->find($id);

        // Si no está en activos, buscar en archivados
        if (!$order) {
            $order = ArchivedOrder::with([
                'branch', 'address.zone', 'coupon', 'deliveryman',
                'items.product', 'items.variant', 'items.extras.extra',
            ])->find($id);
        }

        if (!$order) {
            return $this->error('Pedido no encontrado.', 404);
        }

        // Verificar propiedad
        if ($order->user_id !== $user->id) {
            return $this->error('No tienes permiso para ver este pedido.', 403);
        }

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
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            $dto = \App\DTOs\Order\CreateOrderDTO::fromArray($request->validated());
            $order = $this->orderService->createOrder($user, $dto);
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
     */
    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        if (\Illuminate\Support\Facades\Gate::denies('cancel', $order)) {
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

        $freshOrder = $order->fresh(['branch', 'items.product']);
        if (!$freshOrder) {
            $freshOrder = \App\Models\ArchivedOrder::with(['branch', 'items.product'])
                ->where('user_id', $order->user_id)
                ->where('created_at', $order->created_at)
                ->first();
        }

        return $this->success(
            new OrderResource($freshOrder),
            'Pedido cancelado exitosamente.'
        );
    }
}
