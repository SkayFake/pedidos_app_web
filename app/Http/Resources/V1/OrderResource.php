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
            'id'        => $this->id,
            'status'    => $this->status,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
            // OTP se omite intencionalmente para repartidores — solo se expone al cliente
            'otp' => $this->when(
                auth()->check() && !(auth()->user() instanceof \App\Models\Deliveryman),
                $this->otp
            ),

            'zone_name' => $this->whenLoaded('address', fn () => $this->address->zone?->name),

            // ── Montos formateados ────────────────────
            'subtotal'            => number_format((float) $this->subtotal, 2, '.', ''),
            'subtotal_fmt'        => '$' . number_format((float) $this->subtotal, 2),
            'delivery_fee'        => number_format((float) $this->delivery_fee, 2, '.', ''),
            'delivery_fee_fmt'    => '$' . number_format((float) $this->delivery_fee, 2),
            'deliveryman_payout'     => number_format((float) $this->deliveryman_payout, 2, '.', ''),
            'deliveryman_payout_fmt' => '$' . number_format((float) $this->deliveryman_payout, 2),
            'discount_amount'     => number_format((float) $this->discount_amount, 2, '.', ''),
            'discount_amount_fmt' => '$' . number_format((float) $this->discount_amount, 2),
            'total'               => number_format((float) $this->total, 2, '.', ''),
            'total_fmt'           => '$' . number_format((float) $this->total, 2),

            // ── Cliente (user) ──────────────────────
            'user' => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'phone' => $this->user->phone,
            ]),

            // ── Sucursal ───────────────────────────
            'branch' => $this->whenLoaded('branch', fn () => [
                'id'        => $this->branch->id,
                'name'      => $this->branch->name,
                'address'   => $this->branch->address,
                'latitude'  => $this->branch->latitude,
                'longitude' => $this->branch->longitude,
            ]),

            // ── Dirección de entrega ─────────────────
            'address' => $this->whenLoaded('address', fn () => [
                'id'         => $this->address->id,
                'label'      => $this->address->label,
                'street'     => $this->address->street,
                'references' => $this->address->references,
                'latitude'   => $this->address->latitude,
                'longitude'  => $this->address->longitude,
            ]),

            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            // ── Cupón (solo código, no datos internos) ───
            'coupon_code' => $this->whenLoaded('coupon', fn () => $this->coupon?->code),

            // ── Promociones aplicadas ──────────────────
            'is_first_order_promo'   => (bool) $this->is_first_order_promo,
            'is_free_delivery_promo' => (bool) $this->is_free_delivery_promo,
            'is_loyalty_discount'    => (bool) $this->is_loyalty_discount,

            // ── Notas del cliente ───────────────────
            'notes'               => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,

            // ── Timestamps de estado ──────────────────
            'confirmed_at'     => $this->confirmed_at?->format('d M Y, h:i A'),
            'assigned_at'      => $this->assigned_at?->format('d M Y, h:i A'),
            'delivered_at'     => $this->delivered_at?->format('d M Y, h:i A'),
            'delivered_at_iso' => $this->delivered_at?->toIso8601String(),
            'cancelled_at'     => $this->cancelled_at?->format('d M Y, h:i A'),
            'created_at'       => $this->created_at?->format('d M Y, h:i A'),
            'reviewed_at'      => $this->reviewed_at?->format('d M Y, h:i A'),

            // Repartidor (para pantalla de reseña en Flutter)
            'deliveryman' => $this->whenLoaded('deliveryman', fn () => [
                'id'    => $this->deliveryman->id,
                'name'  => $this->deliveryman->name,
                'photo' => $this->deliveryman->profile_photo
                    ? (str_starts_with($this->deliveryman->profile_photo, 'http')
                        ? $this->deliveryman->profile_photo
                        : asset('storage/' . $this->deliveryman->profile_photo))
                    : null,
            ]),
        ];
    }
}
