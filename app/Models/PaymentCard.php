<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCard extends Model
{
    protected $fillable = [
        'user_id',
        'card_type',
        'last_four',
        'provider_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
