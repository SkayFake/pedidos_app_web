<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_deliverable' => 'boolean',
            'is_active' => 'boolean',
            'allow_out_of_zone_delivery' => 'boolean',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}