<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivedOrderItemExtra extends Model
{
    protected $table = 'archived_order_item_extras';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function orderItem()
    {
        return $this->belongsTo(ArchivedOrderItem::class, 'order_item_id');
    }

    public function extra()
    {
        return $this->belongsTo(ProductExtra::class, 'extra_id');
    }
}
