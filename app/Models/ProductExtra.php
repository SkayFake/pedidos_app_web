<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductExtra extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItemExtras()
    {
        return $this->hasMany(OrderItemExtra::class, 'extra_id');
    }
}