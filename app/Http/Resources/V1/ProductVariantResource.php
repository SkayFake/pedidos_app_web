<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'price_modifier'     => number_format((float) $this->price_modifier, 2, '.', ''),
            'price_modifier_fmt' => ($this->price_modifier >= 0 ? '+$' : '-$') . number_format(abs((float) $this->price_modifier), 2),
            'is_default'         => (bool) $this->is_default,
            'is_available'       => (bool) $this->is_available,
        ];
    }
}
