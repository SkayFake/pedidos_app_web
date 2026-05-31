<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\Branch;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function booted(): void
    {
        $user = auth('admin')->user();

        if (!$user) {
            return;
        }

        // Redirigir a cada rol a su página propia
        if ($user->isOperator()) {
            $this->redirect(\App\Filament\Pages\OperatorDashboard::getUrl());
            return;
        }

        if ($user->isKitchen()) {
            $this->redirect(\App\Filament\Pages\KitchenDisplay::getUrl());
            return;
        }

        if ($user->isSuperAdmin()) {
            $this->redirect(\App\Filament\Pages\SuperAdminDashboard::getUrl());
            return;
        }

        // branch_admin se queda en el dashboard normal
    }

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();
        // Permitir acceso a todos los usuarios autenticados activos
        // (la redirección se maneja en mount())
        return $user && $user->is_active;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('Filtrar por Sucursal')
                    ->options(Branch::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth('admin')->user()?->isSuperAdmin()),
            ])
            ->columns(3);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DashboardStats::class,
            \App\Filament\Widgets\RevenueChart::class,
            \App\Filament\Widgets\TopProductsChart::class,
        ];
    }
}
