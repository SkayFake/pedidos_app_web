<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Sucursal (Vacío = Super Admin)'),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->rules(['email:rfc,dns'])
                    ->required()
                    ->maxLength(150),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8)
                    ->maxLength(255),
                Select::make('role')
                    ->label('Rol')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'branch_admin' => 'Administrador de Sucursal',
                        'operator' => 'Operador',
                        'kitchen' => 'Cocina',
                    ])
                    ->default('operator')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),
            ]);
    }
}
