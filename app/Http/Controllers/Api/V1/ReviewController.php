<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FoodReview;
use App\Models\DeliverymanReview;
use App\Models\Order;
use App\Models\ArchivedOrder;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function store(Request $request, int $orderId): JsonResponse
    {
        $order = Order::find($orderId) ?? ArchivedOrder::find($orderId);

        if (!$order) {
            return $this->error('Pedido no encontrado.', 404);
        }
        $user = auth()->user();

        // 1. Verificar que el pedido le pertenece al usuario
        if ($order->user_id !== $user->id) {
            return $this->error('No tienes permiso para reseñar este pedido.', 403);
        }

        // 2. Verificar que está entregado
        if ($order->status !== 'delivered') {
            return $this->error('Solo puedes reseñar pedidos que ya fueron entregados.', 422);
        }

        // 3. Verificar ventana de 24 horas
        if ($order->delivered_at === null || $order->delivered_at->diffInHours(now()) > 24) {
            return $this->error('El período para reseñar este pedido ha expirado (24 horas).', 422);
        }

        // 4. Verificar que no haya sido reseñado antes
        if ($order->reviewed_at !== null) {
            return $this->error('Este pedido ya fue reseñado.', 422);
        }

        $request->validate([
            'products'              => 'nullable|array',
            'products.*.product_id' => 'required_with:products|integer|exists:products,id',
            'products.*.rating'     => 'required_with:products|integer|min:1|max:5',
            'products.*.comment'    => 'nullable|string|max:500',
            'deliveryman'           => 'nullable|array',
            'deliveryman.rating'    => 'required_with:deliveryman|integer|min:1|max:5',
            'deliveryman.comment'   => 'nullable|string|max:500',
        ]);

        // Guardar reseñas de productos
        if ($request->filled('products')) {
            $validProductIds = $order->items()->pluck('product_id')->toArray();

            foreach ($request->products as $productReview) {
                if (!in_array($productReview['product_id'], $validProductIds)) continue;

                FoodReview::updateOrCreate(
                    [
                        'order_id'   => $order->id,
                        'user_id'    => $user->id,
                        'product_id' => $productReview['product_id'],
                    ],
                    [
                        'rating'  => $productReview['rating'],
                        'comment' => $productReview['comment'] ?? null,
                    ]
                );

                // Actualizar promedio de estrellas del producto
                $avg = FoodReview::where('product_id', $productReview['product_id'])->avg('rating');
                if ($avg !== null) {
                    \App\Models\Product::where('id', $productReview['product_id'])
                        ->update(['stars' => round($avg, 1)]);
                }
            }
        }

        // Guardar reseña del repartidor
        if ($request->filled('deliveryman') && $order->deliveryman_id !== null) {
            DeliverymanReview::updateOrCreate(
                [
                    'order_id'       => $order->id,
                    'user_id'        => $user->id,
                    'deliveryman_id' => $order->deliveryman_id,
                ],
                [
                    'rating'  => $request->input('deliveryman.rating'),
                    'comment' => $request->input('deliveryman.comment') ?? null,
                ]
            );
        }

        // Marcar pedido como reseñado
        $order->update(['reviewed_at' => now()]);

        return $this->success(null, 'Gracias por tus reseñas.');
    }
}
