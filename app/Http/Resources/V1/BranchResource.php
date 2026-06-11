<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $scheduleService = app(\App\Services\BranchScheduleService::class);
        $availability = $scheduleService->checkAvailability($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->is_active,
            'is_open_now' => $availability['is_open'],
            'schedule' => $scheduleService->getFullSchedule($this->resource),
        ];
    }
}
