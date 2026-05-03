<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductExtraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'price'        => number_format((float) $this->price, 2, '.', ''),
            'price_fmt'    => '$' . number_format((float) $this->price, 2),
            'is_available' => (bool) $this->is_available,
        ];
    }
}
