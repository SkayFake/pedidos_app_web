<?php

namespace App\Services;

use App\Models\Order;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Otorga puntos al cliente cuando su pedido llega a estado 'delivered'.
     * Regla: 1 punto por cada $1 gastado en el subtotal (redondeado hacia abajo).
     */
    public function earnPointsForOrder(Order $order): void
    {
        // Solo aplica si el pedido fue entregado
        if ($order->status !== 'delivered') {
            return;
        }

        // Evitar dar puntos múltiples veces si por error se llama de nuevo
        $alreadyEarned = LoyaltyTransaction::where('order_id', $order->id)
            ->where('type', 'earned')
            ->where('description', "Compra de pedido #{$order->id}")
            ->exists();

        if ($alreadyEarned) {
            return;
        }

        $pointsToEarn = (int) floor($order->subtotal);

        if ($pointsToEarn > 0) {
            DB::transaction(function () use ($order, $pointsToEarn) {
                LoyaltyTransaction::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'type' => 'earned',
                    'points' => $pointsToEarn,
                    'description' => "Compra de pedido #{$order->id}",
                ]);

                $order->user->increment('loyalty_points', $pointsToEarn);
                $order->user->increment('lifetime_points', $pointsToEarn);
                
                // Chequear hitos (milestones)
                $this->checkRewardMilestones($order->user);
            });
        }
    }

    /**
     * Verifica si el usuario alcanzó algún hito de puntos y le envía su cupón.
     */
    protected function checkRewardMilestones($user): void
    {
        // Traer hitos activos ordenados
        $milestones = \App\Models\RewardMilestone::with('coupon')->where('is_active', true)->orderBy('points_required', 'asc')->get();

        foreach ($milestones as $milestone) {
            if ($user->lifetime_points >= $milestone->points_required) {
                // Verificar si ya lo reclamó
                $alreadyAchieved = \App\Models\UserMilestone::where('user_id', $user->id)
                    ->where('milestone_id', $milestone->id)
                    ->exists();

                if (!$alreadyAchieved) {
                    // Marcar como alcanzado
                    \App\Models\UserMilestone::create([
                        'user_id' => $user->id,
                        'milestone_id' => $milestone->id,
                        'achieved_at' => now(),
                    ]);

                    // Despachar job para generar y enviar el cupón
                    if ($milestone->coupon) {
                        $message = "¡Has alcanzado los {$milestone->points_required} puntos de fidelidad! Aquí tienes tu recompensa.";
                        \App\Jobs\SendIncentiveCouponJob::dispatch($user, $milestone->coupon, $message);
                    }
                }
            }
        }
    }

    /**
     * Resta puntos al cliente cuando utiliza la promoción de puntos en un pedido.
     * Esto se debe llamar al momento de CREAR el pedido.
     */
    public function deductPointsForOrder(Order $order, int $points): void
    {
        if ($points <= 0) return;

        DB::transaction(function () use ($order, $points) {
            LoyaltyTransaction::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'type' => 'redeemed',
                'points' => -$points, // Negativo para indicar que se restan
                'description' => "Canje de descuento en pedido #{$order->id}",
            ]);

            $order->user->decrement('loyalty_points', $points);
        });
    }

    /**
     * Reembolsa los puntos al cliente si el pedido es cancelado.
     */
    public function refundPointsForOrder(Order $order): void
    {
        // Solo aplica si el pedido tiene descuento de lealtad
        if (!$order->is_loyalty_discount) {
            return;
        }

        // Buscar la transacción donde se restaron los puntos
        $transaction = LoyaltyTransaction::where('order_id', $order->id)
            ->where('type', 'redeemed')
            ->first();

        if ($transaction) {
            // Verificar si ya fue reembolsado
            $alreadyRefunded = LoyaltyTransaction::where('order_id', $order->id)
                ->where('type', 'earned') // Lo ingresamos como ganancia de nuevo
                ->where('description', "Reembolso por cancelación de pedido #{$order->id}")
                ->exists();

            if (!$alreadyRefunded) {
                $pointsToRefund = abs($transaction->points);
                
                DB::transaction(function () use ($order, $pointsToRefund) {
                    LoyaltyTransaction::create([
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'type' => 'earned', // Se suma de vuelta
                        'points' => $pointsToRefund,
                        'description' => "Reembolso por cancelación de pedido #{$order->id}",
                    ]);

                    $order->user->increment('loyalty_points', $pointsToRefund);
                });
            }
        }
    }
}
