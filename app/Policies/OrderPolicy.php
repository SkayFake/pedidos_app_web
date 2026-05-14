<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determina si el usuario puede ver el pedido.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    /**
     * Determina si el usuario puede cancelar el pedido.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
}
