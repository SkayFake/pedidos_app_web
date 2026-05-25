<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Deliveryman extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'deliverymen';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(DeliverymanReview::class);
    }

    protected function averageRating(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->reviews()->avg('rating') ?? 0, 1),
        );
    }

    protected function totalReviews(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->reviews()->count(),
        );
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}