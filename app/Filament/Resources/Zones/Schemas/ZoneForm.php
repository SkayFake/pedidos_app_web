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
                \Filament\Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Sucursal')
                    ->prefixIcon('heroicon-o-building-storefront')
                    ->nullable(),
                TextInput::make('name')
                    ->label('Nombre de la Zona')
                    ->prefixIcon('heroicon-o-map')
                    ->required(),
                TextInput::make('city')
                    ->label('Ciudad / Municipio')
                    ->prefixIcon('heroicon-o-map-pin')
                    ->required(),
                TextInput::make('delivery_fee')
                    ->label('Tarifa Base de Envío ($)')
                    ->prefixIcon('heroicon-o-currency-dollar')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('base_distance_km')
                    ->label('Radio de Cobertura (km)')
                    ->hint('Distancia máxima cubierta por la tarifa base')
                    ->prefixIcon('heroicon-o-arrows-pointing-out')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('extra_per_km')
                    ->label('Cobro Extra por km ($)')
                    ->hint('Solo aplica si se permiten entregas fuera de zona')
                    ->prefixIcon('heroicon-o-currency-dollar')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('allow_out_of_zone_delivery')
                    ->label('Permitir entregas fuera de la zona base')
                    ->helperText('Si se activa, el cliente podrá pedir aunque esté fuera del radio, calculándose el costo extra por distancia.')
                    ->default(false),
                Toggle::make('is_deliverable')
                    ->label('Tiene Cobertura General')
                    ->default(true)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Zona Activa')
                    ->default(true)
                    ->required(),
            ]);
    }
}
