<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCard extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'card_number',
    ];

    protected function casts(): array
    {
        return [
            'card_number' => 'encrypted',
            'card_holder' => 'encrypted',
            'expiry_date' => 'encrypted',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
