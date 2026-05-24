<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'product_id'     => $this->product?->id,
            'product_name'   => $this->product?->name,
            'product_image'  => $this->product?->image
                ? (str_starts_with($this->product->image, 'http') ? $this->product->image : asset('storage/' . $this->product->image))
                : null,
            'variant_name'   => $this->variant?->name,
            'quantity'       => (int) $this->quantity,
            'unit_price'     => number_format((float) $this->unit_price, 2, '.', ''),
            'unit_price_fmt' => '$' . number_format((float) $this->unit_price, 2),
            'subtotal'       => number_format((float) $this->subtotal, 2, '.', ''),
            'subtotal_fmt'   => '$' . number_format((float) $this->subtotal, 2),
            'extras'         => OrderItemExtraResource::collection($this->whenLoaded('extras')),
        ];
    }
}
