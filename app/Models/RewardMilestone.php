<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardMilestone extends Model
{
    protected $fillable = [
        'points_required',
        'coupon_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points_required' => 'integer',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
