<?php

namespace App\Filament\Resources\Zones\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('city')
                    ->label('Ciudad')
                    ->required(),
                TextInput::make('delivery_fee')
                    ->label('Tarifa de Envío')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('is_deliverable')
                    ->label('Tiene Cobertura')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->required(),
            ]);
    }
}
