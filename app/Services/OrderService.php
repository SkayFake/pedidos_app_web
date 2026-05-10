<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductExtra;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Tarifa fija de envío.
     */
    private const DELIVERY_FEE = 2.00;

    public function __construct(
        private readonly PromotionService $promotionService,
        private readonly LoyaltyService $loyaltyService,
    ) {}

    /**
     * Calcula los precios de los ítems del pedido server-side.
     *
     * JAMÁS confía en montos enviados por el cliente.
     * Lee precios de: products.base_price, product_variants.price_modifier, product_extras.price
     *
     * @param  array  $items  Array de ítems del payload validado.
     * @return array  ['items_data' => [...], 'subtotal' => float]
     *
     * @throws \App\Exceptions\OrderValidationException
     */
    public function calculateItemsPricing(array $items): array
    {
        $orderItemsData = [];
        $subtotal = 0;

        foreach ($items as $itemPayload) {
            // ── Producto ───────────────────────────────────────
            $product = Product::find($itemPayload['product_id']);

            if (!$product || !$product->is_available) {
                throw new \App\Exceptions\OrderValidationException(
                    "El producto \"{$product?->name}\" no está disponible."
                );
            }

            // Precio base del producto (leído de BD, nunca del cliente)
            $unitPrice = (float) $product->base_price;

            // ── Variante (price_modifier) ──────────────────────
            $variantId = $itemPayload['variant_id'] ?? null;
            if ($variantId) {
                $variant = ProductVariant::where('id', $variantId)
                    ->where('product_id', $product->id)
                    ->first();

                if (!$variant || !$variant->is_available) {
                    throw new \App\Exceptions\OrderValidationException(
                        "La variante seleccionada para \"{$product->name}\" no está disponible."
                    );
                }

                // El price_modifier puede ser positivo (+$1.50) o negativo (-$0.50)
                $unitPrice += (float) $variant->price_modifier;
            }

            // Asegurar que el precio unitario nunca sea negativo
            $unitPrice = max(0, $unitPrice);

            $quantity = (int) $itemPayload['quantity'];
            $itemSubtotal = round($unitPrice * $quantity, 2);

            // ── Extras ─────────────────────────────────────────
            $extrasData = [];
            if (!empty($itemPayload['extras'])) {
                foreach ($itemPayload['extras'] as $extraPayload) {
                    $extra = ProductExtra::where('id', $extraPayload['extra_id'])
                        ->where('product_id', $product->id)
                        ->first();

                    if (!$extra || !$extra->is_available) {
                        throw new \App\Exceptions\OrderValidationException(
                            "El extra seleccionado para \"{$product->name}\" no está disponible."
                        );
                    }

                    $extraQty = (int) $extraPayload['quantity'];
                    $extraUnitPrice = (float) $extra->price;
                    $itemSubtotal += round($extraUnitPrice * $extraQty, 2);

                    $extrasData[] = [
                        'extra_id'   => $extra->id,
                        'quantity'   => $extraQty,
                        'unit_price' => $extraUnitPrice,
                    ];
                }
            }

            $orderItemsData[] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
                'subtotal'   => $itemSubtotal,
                'extras'     => $extrasData,
            ];

            $subtotal += $itemSubtotal;
        }

        return [
            'items_data' => $orderItemsData,
            'subtotal'   => round($subtotal, 2),
        ];
    }

    /**
     * Crea el pedido completo con cálculo seguro de precios.
     *
     * Flujo:
     * 1. Calcula subtotal leyendo precios de BD
     * 2. Resuelve cupón
     * 3. Aplica promociones (1er pedido, #11, lealtad, cupón)
     * 4. Crea la orden + ítems + extras en transacción
     * 5. Registra uso de cupón y deduce puntos si aplica
     *
     * @throws \App\Exceptions\OrderValidationException
     * @throws \Throwable
     */
    public function createOrder(User $user, array $validated): Order
    {
        // ── 1. Validar propiedad de la dirección ───────────────
        $address = $user->addresses()->find($validated['address_id']);
        if (!$address) {
            throw new \App\Exceptions\OrderValidationException(
                'La dirección seleccionada no te pertenece.'
            );
        }

        // ── 2. Validar sucursal activa ─────────────────────────
        $branch = Branch::find($validated['branch_id']);
        if (!$branch || !$branch->is_active) {
            throw new \App\Exceptions\OrderValidationException(
                'La sucursal seleccionada no está disponible.'
            );
        }

        // ── 3. Calcular precios server-side ────────────────────
        $pricing = $this->calculateItemsPricing($validated['items']);

        // ── 4. Resolver cupón ──────────────────────────────────
        $coupon = null;
        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', $validated['coupon_code'])->first();
        }

        // ── 5. Calcular promociones y descuentos ───────────────
        $promoResult = $this->promotionService->calculatePromotions(
            user: $user,
            branch: $branch,
            subtotal: $pricing['subtotal'],
            deliveryFee: self::DELIVERY_FEE,
            usePoints: $validated['use_loyalty_points'] ?? false,
            coupon: $coupon,
        );

        // ── 6. Crear en transacción atómica ────────────────────
        return DB::transaction(function () use ($user, $validated, $promoResult, $pricing, $coupon) {
            $order = Order::create([
                'user_id'                => $user->id,
                'branch_id'              => $validated['branch_id'],
                'address_id'             => $validated['address_id'],
                'coupon_id'              => $promoResult['applied_coupon_id'],
                'otp'                    => (string) mt_rand(1000, 9999),
                'status'                 => 'pending',
                'subtotal'               => $promoResult['subtotal'],
                'delivery_fee'           => $promoResult['delivery_fee_final'],
                'discount_amount'        => $promoResult['discount_amount'],
                'total'                  => $promoResult['total'],
                'is_first_order_promo'   => $promoResult['is_first_order_promo'],
                'is_free_delivery_promo' => $promoResult['is_free_delivery_promo'],
                'is_loyalty_discount'    => $promoResult['is_loyalty_discount'],
                'notes'                  => $validated['notes'] ?? null,
            ]);

            // Crear ítems del pedido con precios snapshot
            foreach ($pricing['items_data'] as $itemData) {
                $orderItem = $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'variant_id' => $itemData['variant_id'],
                    'quantity'   => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal'   => $itemData['subtotal'],
                ]);

                // Crear extras del ítem con precios snapshot
                foreach ($itemData['extras'] as $extraData) {
                    $orderItem->extras()->create($extraData);
                }
            }

            // Registrar uso del cupón
            if ($promoResult['applied_coupon_id'] && $coupon) {
                $coupon->increment('used_count');
                $coupon->uses()->create([
                    'user_id'  => $order->user_id,
                    'order_id' => $order->id,
                    'used_at'  => now(),
                ]);
            }

            // Deducir puntos de lealtad
            if ($promoResult['is_loyalty_discount'] && $promoResult['points_to_deduct'] > 0) {
                $this->loyaltyService->deductPointsForOrder($order, $promoResult['points_to_deduct']);
            }

            return $order;
        });
    }

    /**
     * Obtiene la tarifa de envío fija.
     */
    public function getDeliveryFee(): float
    {
        return self::DELIVERY_FEE;
    }
}
