<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivedOrderItem extends Model
{
    protected $table = 'archived_order_items';
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(ArchivedOrder::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function extras()
    {
        return $this->hasMany(ArchivedOrderItemExtra::class, 'order_item_id');
    }
}
