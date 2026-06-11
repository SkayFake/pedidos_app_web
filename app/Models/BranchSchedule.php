<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchSchedule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
