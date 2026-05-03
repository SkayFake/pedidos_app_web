<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_recommended' => 'boolean',
            'is_popular' => 'boolean',
            'stars' => 'float',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function extras()
    {
        return $this->hasMany(ProductExtra::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}