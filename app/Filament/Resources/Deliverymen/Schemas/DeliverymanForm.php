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
                    ->maxLength(100),
                Select::make('branch_id')
                    ->label('Sucursal (Opcional, Nulo = Global)')
                    ->relationship('branch', 'name')
                    ->nullable(),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->rules(['email:rfc,dns'])
                    ->required()
                    ->maxLength(150),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->rules(['regex:/^\+503\s?[267]\d{7}$/'])
                    ->helperText('Formato: +503 7890 1234')
                    ->required()
                    ->maxLength(20),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->hiddenOn('edit')
                    ->minLength(8)
                    ->maxLength(255),
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
