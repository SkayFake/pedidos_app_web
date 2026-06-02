<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'loyalty_points' => 'integer',
            'total_completed_orders' => 'integer',
        ];
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image ? url('storage/' . $this->image) : null;
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function foodReviews()
    {
        return $this->hasMany(FoodReview::class);
    }

    public function deliverymanReviews()
    {
        return $this->hasMany(DeliverymanReview::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function couponUses()
    {
        return $this->hasMany(CouponUse::class);
    }
}