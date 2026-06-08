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
                    ->required()
                    ->rules(['regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'])
                    ->validationMessages([
                        'regex' => 'El nombre solo puede contener letras y espacios.',
                    ])
                    ->extraInputAttributes([
                        'autocomplete' => 'off',
                        'x-on:input' => "\$el.value = \$el.value.replace(/[^a-zA-Z\u00e1\u00e9\u00ed\u00f3\u00fa\u00c1\u00c9\u00cd\u00d3\u00da\u00f1\u00d1\u00fc\u00dc\s]/g, ''); \$el.dispatchEvent(new Event('input'))",
                    ]),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->rules(['regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'])
                    ->validationMessages([
                        'regex' => 'El correo electrónico debe tener un formato válido (ej. usuario@gmail.com) y no contener espacios.',
                    ])
                    ->required()
                    ->maxLength(150)
                    ->extraInputAttributes([
                        'autocomplete' => 'new-email',
                        'x-on:input' => "\$el.value = \$el.value.replace(/\s/g, ''); \$el.dispatchEvent(new Event('input'))",
                    ]),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->afterStateHydrated(fn (TextInput $component) => $component->state(''))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8)
                    ->maxLength(255)
                    ->extraInputAttributes([
                        'autocomplete' => 'new-password',
                    ]),
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
