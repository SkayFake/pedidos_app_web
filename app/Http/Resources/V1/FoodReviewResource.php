<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->user?->name ?? 'Anónimo',
            'user_photo' => $this->user?->photo ? (str_starts_with($this->user->photo, 'http') ? $this->user->photo : asset('storage/' . $this->user->photo)) : null,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'date' => $this->created_at?->diffForHumans(),
            'date_full' => $this->created_at?->format('d M Y'),
        ];
    }
}
