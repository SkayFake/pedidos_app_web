<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'label'      => $this->label,
            'street'     => $this->street,
            'references' => $this->references,
            'latitude'   => $this->latitude ? (float) $this->latitude : null,
            'longitude'  => $this->longitude ? (float) $this->longitude : null,
            'is_default' => (bool) $this->is_default,
            'zone'       => $this->whenLoaded('zone', fn () => [
                'id'   => $this->zone->id,
                'name' => $this->zone->name,
            ]),
        ];
    }
}
