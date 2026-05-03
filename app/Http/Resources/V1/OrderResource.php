<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,

            // ── Montos formateados ─────────────────────────────
            'subtotal'        => number_format((float) $this->subtotal, 2, '.', ''),
            'subtotal_fmt'    => '$' . number_format((float) $this->subtotal, 2),
            'delivery_fee'    => number_format((float) $this->delivery_fee, 2, '.', ''),
            'delivery_fee_fmt'=> '$' . number_format((float) $this->delivery_fee, 2),
            'discount_amount'     => number_format((float) $this->discount_amount, 2, '.', ''),
            'discount_amount_fmt' => '$' . number_format((float) $this->discount_amount, 2),
            'total'           => number_format((float) $this->total, 2, '.', ''),
            'total_fmt'       => '$' . number_format((float) $this->total, 2),

            // ── Relaciones ─────────────────────────────────────
            'branch' => $this->whenLoaded('branch', fn () => [
                'id'      => $this->branch->id,
                'name'    => $this->branch->name,
                'address' => $this->branch->address,
            ]),
            'address' => $this->whenLoaded('address', fn () => [
                'id'     => $this->address->id,
                'label'  => $this->address->label,
                'street' => $this->address->street,
            ]),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            // ── Cupón (solo código, no datos internos) ─────────
            'coupon_code' => $this->whenLoaded('coupon', fn () => $this->coupon?->code),

            // ── Promociones aplicadas ──────────────────────────
            'is_first_order_promo'   => (bool) $this->is_first_order_promo,
            'is_free_delivery_promo' => (bool) $this->is_free_delivery_promo,
            'is_loyalty_discount'    => (bool) $this->is_loyalty_discount,

            // ── Notas del cliente ──────────────────────────────
            'notes'               => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,

            // ── Timestamps de estado ───────────────────────────
            'confirmed_at'  => $this->confirmed_at?->format('d M Y, h:i A'),
            'assigned_at'   => $this->assigned_at?->format('d M Y, h:i A'),
            'delivered_at'  => $this->delivered_at?->format('d M Y, h:i A'),
            'cancelled_at'  => $this->cancelled_at?->format('d M Y, h:i A'),
            'created_at'    => $this->created_at?->format('d M Y, h:i A'),
        ];
    }
}
