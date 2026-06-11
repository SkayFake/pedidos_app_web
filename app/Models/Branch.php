<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function adminUsers()
    {
        return $this->hasMany(AdminUser::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function deliverymen()
    {
        return $this->hasMany(Deliveryman::class);
    }

    public function schedules()
    {
        return $this->hasMany(BranchSchedule::class);
    }

    public function specialSchedules()
    {
        return $this->hasMany(SpecialSchedule::class);
    }
}