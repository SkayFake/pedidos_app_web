<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código')
                    ->required(),
                TextInput::make('description')
                    ->label('Descripción'),
                Select::make('branch_id')
                    ->label('Sucursal (Opcional, Nulo = Global)')
                    ->relationship('branch', 'name')
                    ->nullable(),
                Select::make('type')
                    ->label('Tipo')
                    ->options(['percent' => 'Porcentaje', 'fixed' => 'Fijo', 'free_delivery' => 'Envío gratis'])
                    ->required(),
                TextInput::make('value')
                    ->label('Valor')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0.0),
                TextInput::make('max_discount')
                    ->label('Descuento Máximo')
                    ->helperText('Tope máximo de descuento para cupones tipo porcentaje. Vacío = sin tope.')
                    ->numeric()
                    ->minValue(0)
                    ->nullable(),
                TextInput::make('min_order_amount')
                    ->label('Monto Mínimo de Pedido')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0.0),
                TextInput::make('max_uses_total')
                    ->label('Usos Máximos Totales')
                    ->numeric(),
                TextInput::make('used_count')
                    ->label('Veces Usado')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('expires_at')
                    ->label('Expira'),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),
            ]);
    }
}
