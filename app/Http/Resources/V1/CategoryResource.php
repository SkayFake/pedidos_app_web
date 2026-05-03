<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
        ];
    }
}
