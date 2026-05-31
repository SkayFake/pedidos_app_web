<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cookie;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Bienvenido de vuelta';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Ingresa tus credenciales para acceder al panel de administración.';
    }

    /**
     * Pre-fill the email from cookie if the user previously checked "Recordar correo".
     */
    public function mount(): void
    {
        parent::mount();

        $rememberedEmail = request()->cookie('remember_email');
        if ($rememberedEmail) {
            $this->form->fill(['email' => $rememberedEmail]);
        }
    }

    /**
     * Override authenticate to:
     * - Save email in cookie if "recuérdame" is checked
     * - Always authenticate WITHOUT persistent session (remember = false)
     */
    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        $data = $this->form->getState();

        $rememberEmail = $data['remember'] ?? false;

        // Guardar o borrar la cookie del correo según la selección
        if ($rememberEmail) {
            Cookie::queue('remember_email', $data['email'], 60 * 24 * 365); // 1 año
        } else {
            Cookie::queue(Cookie::forget('remember_email'));
        }

        // Forzar remember = false para no crear sesión persistente
        $data['remember'] = false;
        $this->form->fill($data);

        return parent::authenticate();
    }

    protected function getRedirectUrl(): string
    {
        $user = auth('admin')->user();

        if (!$user) {
            return parent::getRedirectUrl();
        }

        // Operadores → Panel de Operador
        if ($user->isOperator()) {
            return \App\Filament\Pages\OperatorDashboard::getUrl();
        }

        // Cocina → Pantalla de cocina
        if ($user->isKitchen()) {
            return \App\Filament\Pages\KitchenDisplay::getUrl();
        }

        // Super Admin → Métricas Globales
        if ($user->isSuperAdmin()) {
            return \App\Filament\Pages\SuperAdminDashboard::getUrl();
        }

        // Branch Admin → Dashboard principal
        return \App\Filament\Pages\Dashboard::getUrl();
    }
}
