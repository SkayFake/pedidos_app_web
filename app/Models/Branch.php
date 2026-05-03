<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = ['id'];

    public function adminUsers()
    {
        return $this->hasMany(AdminUser::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}