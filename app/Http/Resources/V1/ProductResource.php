<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\FoodReviewResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description'    => $this->description,
            'time_preparation' => $this->time_preparation,
            'base_price'     => number_format((float) $this->base_price, 2, '.', ''),
            'base_price_fmt' => '$' . number_format((float) $this->base_price, 2),
            'image' => $this->image ? str_replace(config('app.url'), config('app.asset_url') ?: config('app.url'), asset('storage/' . $this->image)) : null,
            'is_available' => (bool) $this->is_available,
            'is_recommended' => (bool) $this->is_recommended,
            'is_popular' => (bool) $this->is_popular,
            'stars' => $this->stars ? (float) $this->stars : 0.0,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'extras' => ProductExtraResource::collection($this->whenLoaded('extras')),
            'reviews' => FoodReviewResource::collection($this->whenLoaded('reviews')),
            'reviews_count' => $this->whenLoaded('reviews', fn() => $this->reviews->count(), 0),
        ];
    }
}
