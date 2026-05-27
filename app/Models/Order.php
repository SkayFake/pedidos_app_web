<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_first_order_promo' => 'boolean',
            'is_free_delivery_promo' => 'boolean',
            'is_loyalty_discount' => 'boolean',
            'cancelled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'assigned_at' => 'datetime',
            'delivered_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function deliveryman()
    {
        return $this->belongsTo(Deliveryman::class);
    }

    public function address()
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function foodReview()
    {
        return $this->hasOne(FoodReview::class);
    }

    public function deliverymanReview()
    {
        return $this->hasOne(DeliverymanReview::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}