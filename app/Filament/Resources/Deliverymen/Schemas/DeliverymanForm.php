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
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Select::make('vehicle_type')
                    ->options(['motorcycle' => 'Motorcycle', 'bicycle' => 'Bicycle', 'car' => 'Car'])
                    ->default('motorcycle')
                    ->required(),
                TextInput::make('license_plate'),
                Toggle::make('is_available')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('active_orders_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
