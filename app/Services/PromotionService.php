<?php

namespace App\Services;

use App\Models\User;
use App\Models\Branch;
use App\Models\Coupon;

class PromotionService
{
    /**
     * Calcula los descuentos y promociones aplicables al pedido.
     */
    public function calculatePromotions(User $user, Branch $branch, float $subtotal, float $deliveryFee, bool $usePoints = false, ?Coupon $coupon = null): array
    {
        $result = [
            'subtotal' => $subtotal,
            'delivery_fee_final' => $deliveryFee,
            'discount_amount' => 0.00,
            'total' => 0.00,
            'is_first_order_promo' => false,
            'is_free_delivery_promo' => false,
            'is_loyalty_discount' => false,
            'points_to_deduct' => 0,
            'applied_coupon_id' => null,
        ];

        // 1. Pedido #11 (Promo Delivery Gratis)
        // Se aplica cada 11vo pedido (después de 10 completados, 20 completados, etc.)
        if ((int) $user->total_completed_orders > 0 && ((int) $user->total_completed_orders % 10) === 0) {
            $result['is_free_delivery_promo'] = true;
            $result['delivery_fee_final'] = 0.00;
        } 
        // 2. Descuento de 1er pedido (si no es el #11, lo cual es obvio pero mantenemos la exclusión)
        elseif ((int) $user->total_completed_orders === 0 && $branch->first_order_discount_percent > 0) {
            $result['is_first_order_promo'] = true;
            $discount = ($deliveryFee * $branch->first_order_discount_percent) / 100;
            $result['delivery_fee_final'] = max(0, $deliveryFee - $discount);
        }
        // 3. Puntos de fidelidad (si el usuario activa la opción y tiene >= 50 puntos y no tuvo delivery gratis por otra promo)
        elseif ($usePoints && $user->loyalty_points >= 50 && $result['delivery_fee_final'] > 0) {
            $discount = ($deliveryFee * 5) / 100; // 5% de descuento en el envío
            if ($discount > 0) {
                $result['is_loyalty_discount'] = true;
                $result['delivery_fee_final'] = max(0, $deliveryFee - $discount);
                $result['points_to_deduct'] = (int) ceil($discount); // 1 punto por cada $1 descontado (mínimo 1)
            }
        }

        // 4. Cupón (Aplica al subtotal o al envío dependiendo del tipo)
        if ($coupon && $coupon->is_active) {
            if (!$coupon->expires_at || $coupon->expires_at->isFuture()) {
                if ($subtotal >= $coupon->min_order_amount) {
                    $canUse = true;
                    // Verificar que el cupón pertenece a la sucursal o es global
                    if ($coupon->branch_id !== null && $coupon->branch_id !== $branch->id) {
                        $canUse = false;
                    }
                    // Verificar usos globales
                    if ($canUse && $coupon->max_uses_total !== null && $coupon->used_count >= $coupon->max_uses_total) {
                        $canUse = false;
                    }
                    // Verificar si este usuario ya lo usó
                    if ($canUse && $user->couponUses()->where('coupon_id', $coupon->id)->exists()) {
                        $canUse = false;
                    }

                    if ($canUse) {
                        $result['applied_coupon_id'] = $coupon->id;
                        if ($coupon->type === 'percent') {
                            $result['discount_amount'] = round(($subtotal * $coupon->value) / 100, 2);
                        } elseif ($coupon->type === 'fixed') {
                            $result['discount_amount'] = min($subtotal, $coupon->value);
                        } elseif ($coupon->type === 'free_delivery') {
                            $result['delivery_fee_final'] = 0.00;
                        }
                    }
                }
            }
        }

        $result['total'] = max(0, $subtotal + $result['delivery_fee_final'] - $result['discount_amount']);

        return $result;
    }
}
