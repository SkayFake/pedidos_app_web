<?php

namespace App\Filament\Resources\Deliverymen\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DeliverymanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100)
                    ->rules(['regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'])
                    ->validationMessages([
                        'regex' => 'El nombre solo puede contener letras y espacios.',
                    ])
                    ->extraInputAttributes([
                        'autocomplete' => 'off',
                        'x-on:input' => "\$el.value = \$el.value.replace(/[^a-zA-Z\u00e1\u00e9\u00ed\u00f3\u00fa\u00c1\u00c9\u00cd\u00d3\u00da\u00f1\u00d1\u00fc\u00dc\s]/g, ''); \$el.dispatchEvent(new Event('input'))",
                    ]),
                Select::make('branch_id')
                    ->label('Sucursal (Opcional, Nulo = Global)')
                    ->relationship('branch', 'name')
                    ->nullable(),
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
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->prefix('+503 ')
                    ->rules(['regex:/^[2567]\d{7}$/'])
                    ->validationMessages([
                        'regex' => 'El teléfono debe tener 8 dígitos y comenzar con 2, 5, 6 o 7.',
                    ])
                    ->required()
                    ->maxLength(8)
                    ->minLength(8)
                    ->formatStateUsing(fn ($state) => $state ? preg_replace('/^\+503\s*/', '', $state) : null)
                    ->dehydrateStateUsing(fn ($state) => $state ? '+503' . $state : null)
                    ->extraInputAttributes([
                        'autocomplete' => 'off',
                        'x-on:input' => "\$el.value = \$el.value.replace(/\D/g, ''); if (\$el.value.length > 0 && !/^[2567]/.test(\$el.value)) { \$el.value = ''; } if (\$el.value.length > 8) { \$el.value = \$el.value.substring(0, 8); } \$el.dispatchEvent(new Event('input'))",
                    ]),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->afterStateHydrated(fn (TextInput $component) => $component->state(''))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->hiddenOn('edit')
                    ->minLength(8)
                    ->maxLength(255)
                    ->extraInputAttributes([
                        'autocomplete' => 'new-password',
                    ]),
                Select::make('vehicle_type')
                    ->label('Tipo Vehículo')
                    ->options(['motorcycle' => 'Motocicleta', 'bicycle' => 'Bicicleta', 'car' => 'Carro'])
                    ->default('motorcycle')
                    ->required(),
                TextInput::make('license_plate')
                    ->label('Placa'),
                Toggle::make('is_available')
                    ->label('Disponible')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),
                TextInput::make('active_orders_count')
                    ->label('Pedidos Activos')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
