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
        ];
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }
}