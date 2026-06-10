<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class AdminUser extends Authenticatable implements FilamentUser
{
    use Notifiable {
        notify as traitsNotify;
    }
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isBranchAdmin(): bool
    {
        return $this->role === 'branch_admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isKitchen(): bool
    {
        return $this->role === 'kitchen';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = route('filament.admin.auth.password-reset.reset', [
            'token' => $token,
            'email' => $this->email,
        ]);

        $this->notify(new \App\Notifications\AdminResetPasswordNotification($token, $url));
    }

    public function notify($instance)
    {
        if ($instance instanceof \Illuminate\Auth\Notifications\ResetPassword) {
            $url = $instance->url ?? route('filament.admin.auth.password-reset.reset', [
                'token' => $instance->token,
                'email' => $this->email,
            ]);
            $instance = new \App\Notifications\AdminResetPasswordNotification($instance->token, $url);
        }

        $this->traitsNotify($instance);
    }
}