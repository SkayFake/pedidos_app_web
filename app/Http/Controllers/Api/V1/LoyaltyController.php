<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RewardMilestone;
use App\Models\LoyaltyTransaction;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Fidelidad
 *
 * Endpoints para que el usuario consulte su saldo de puntos, 
 * progreso de hitos (milestones), historial de transacciones y cupones ganados.
 */
class LoyaltyController extends Controller
{
    use ApiResponse;

    /**
     * Perfil de Fidelidad
     *
     * Devuelve los puntos actuales, los puntos históricos y el progreso 
     * respecto a los hitos (milestones) de recompensas.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Perfil de fidelidad obtenido.",
     *   "data": {
     *     "loyalty_points": 150,
     *     "lifetime_points": 300,
     *     "current_milestone": {
     *       "id": 1,
     *       "name": "Nivel Plata",
     *       "points_required": 250
     *     },
     *     "next_milestone": {
     *       "id": 2,
     *       "name": "Nivel Oro",
     *       "points_required": 500
     *     }
     *   }
     * }
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();

        // Buscar el último hito alcanzado
        $currentMilestone = RewardMilestone::where('is_active', true)
            ->where('points_required', '<=', $user->lifetime_points)
            ->orderBy('points_required', 'desc')
            ->first();

        // Buscar el próximo hito a alcanzar
        $nextMilestone = RewardMilestone::where('is_active', true)
            ->where('points_required', '>', $user->lifetime_points)
            ->orderBy('points_required', 'asc')
            ->first();

        return $this->success([
            'loyalty_points'    => $user->loyalty_points,
            'lifetime_points'   => $user->lifetime_points,
            'current_milestone' => $currentMilestone ? [
                'id'              => $currentMilestone->id,
                'name'            => $currentMilestone->name,
                'points_required' => $currentMilestone->points_required,
            ] : null,
            'next_milestone'    => $nextMilestone ? [
                'id'              => $nextMilestone->id,
                'name'            => $nextMilestone->name,
                'points_required' => $nextMilestone->points_required,
            ] : null,
        ], 'Perfil de fidelidad obtenido.');
    }

    /**
     * Historial de Puntos
     *
     * Devuelve la lista paginada de transacciones de puntos (ganados o gastados).
     *
     * @queryParam per_page integer Cantidad por página. Example: 15
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = min($request->integer('per_page', 15), 50);

        $transactions = LoyaltyTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success($transactions, 'Historial de puntos.');
    }

    /**
     * Mis Cupones
     *
     * Lista los cupones que pertenecen exclusivamente al usuario y que aún 
     * están vigentes (no expirados, no agotados, y no usados por él mismo).
     */
    public function coupons(): JsonResponse
    {
        $user = auth()->user();

        // Obtener cupones asignados al usuario que están activos
        $coupons = Coupon::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_template', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->get();

        // Filtrar aquellos que no hayan superado el límite global de usos (por seguridad)
        // Y aquellos que el usuario no haya usado ya.
        $validCoupons = $coupons->filter(function ($coupon) use ($user) {
            if ($coupon->max_uses_total !== null && $coupon->used_count >= $coupon->max_uses_total) {
                return false;
            }
            if ($user->couponUses()->where('coupon_id', $coupon->id)->exists()) {
                return false;
            }
            return true;
        })->values();

        // Mapear la respuesta para el cliente móvil
        $mappedCoupons = $validCoupons->map(function ($coupon) {
            return [
                'id'               => $coupon->id,
                'code'             => $coupon->code,
                'description'      => $coupon->description,
                'type'             => $coupon->type,
                'value'            => $coupon->value,
                'min_order_amount' => $coupon->min_order_amount,
                'expires_at'       => $coupon->expires_at,
            ];
        });

        return $this->success($mappedCoupons, 'Tus cupones disponibles.');
    }
}
