<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemExtraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'extra_name'     => $this->extra?->name,
            'quantity'       => (int) $this->quantity,
            'unit_price'     => number_format((float) $this->unit_price, 2, '.', ''),
            'unit_price_fmt' => '$' . number_format((float) $this->unit_price, 2),
        ];
    }
}
