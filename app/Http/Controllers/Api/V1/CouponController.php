<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Cupones
 *
 * Endpoint para validar cupones de descuento antes de crear un pedido.
 */
class CouponController extends Controller
{
    use ApiResponse;

    /**
     * Validar cupón
     *
     * Valida un código de cupón y retorna el descuento estimado sin aplicarlo.
     * Útil para mostrar el descuento en la UI antes de confirmar el pedido.
     *
     * @bodyParam code string required Código del cupón. Example: BIENVENIDO10
     * @bodyParam order_amount numeric required Monto del pedido (subtotal). Example: 25.50
     * @bodyParam branch_id integer required ID de la sucursal. Example: 1
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'order_amount' => 'required|numeric|min:0',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $user = auth()->user();
        $coupon = Coupon::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return $this->error('El cupón ingresado no existe o no está activo.', 422);
        }

        // Verificar expiración
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return $this->error('Este cupón ha expirado.', 422);
        }

        // Verificar monto mínimo
        if ($request->order_amount < $coupon->min_order_amount) {
            return $this->error(
                "El pedido mínimo para este cupón es \${$coupon->min_order_amount}.",
                422
            );
        }

        // Verificar usos globales
        if ($coupon->max_uses_total !== null && $coupon->used_count >= $coupon->max_uses_total) {
            return $this->error('Este cupón ha alcanzado su límite de usos.', 422);
        }

        // Verificar si el usuario ya lo usó
        if ($user->couponUses()->where('coupon_id', $coupon->id)->exists()) {
            return $this->error('Ya has utilizado este cupón anteriormente.', 422);
        }

        // Verificar sucursal
        if ($coupon->branch_id !== null && $coupon->branch_id !== (int) $request->branch_id) {
            return $this->error('Este cupón no es válido para la sucursal seleccionada.', 422);
        }

        // Calcular descuento estimado
        $discount = 0;
        $message = '';

        switch ($coupon->type) {
            case 'percent':
                $discount = round(($request->order_amount * $coupon->value) / 100, 2);
                // Aplicar tope máximo si existe
                if ($coupon->max_discount !== null && $discount > $coupon->max_discount) {
                    $discount = $coupon->max_discount;
                }
                $message = "{$coupon->value}% de descuento aplicado.";
                break;
            case 'fixed':
                $discount = min($request->order_amount, $coupon->value);
                $message = "Descuento de \${$discount} aplicado.";
                break;
            case 'free_delivery':
                $message = 'Envío gratis aplicado.';
                break;
        }

        return $this->success([
            'coupon' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'description' => $coupon->description,
            ],
            'discount_amount' => number_format($discount, 2, '.', ''),
            'discount_amount_fmt' => '$' . number_format($discount, 2),
            'estimated_total' => number_format(max(0, $request->order_amount - $discount), 2, '.', ''),
            'estimated_total_fmt' => '$' . number_format(max(0, $request->order_amount - $discount), 2),
        ], $message);
    }
}
