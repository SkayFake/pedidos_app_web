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
                    ->required(),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->required(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required(),
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
