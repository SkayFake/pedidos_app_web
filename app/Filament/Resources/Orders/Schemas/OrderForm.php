<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Cliente')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(),
                Select::make('branch_id')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->required()
                    ->disabled(),
                Select::make('deliveryman_id')
                    ->label('Repartidor')
                    ->relationship('deliveryman', 'name')
                    ->searchable(),
                Select::make('address_id')
                    ->label('Dirección')
                    ->relationship('address', 'street')
                    ->required()
                    ->disabled(),
                Select::make('coupon_id')
                    ->label('Cupón')
                    ->relationship('coupon', 'code')
                    ->disabled(),
                Select::make('status')
                    ->label('Estado')
                    ->options([
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'preparing' => 'En Preparación',
            'assigned' => 'Listo para enviar',
            'on_way' => 'En Camino',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
        ])
                    ->default('pending')
                    ->required(),
                TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->required()
                    ->numeric()
                    ->disabled(),
                TextInput::make('delivery_fee')
                    ->label('Tarifa de Envío')
                    ->required()
                    ->numeric()
                    ->disabled(),
                TextInput::make('discount_amount')
                    ->label('Monto Descuento')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->disabled(),
                TextInput::make('total')
                    ->label('Total')
                    ->required()
                    ->numeric()
                    ->disabled(),
                Toggle::make('is_first_order_promo')
                    ->label('Promo 1er Pedido')
                    ->disabled(),
                Toggle::make('is_free_delivery_promo')
                    ->label('Envío Gratis')
                    ->disabled(),
                Toggle::make('is_loyalty_discount')
                    ->label('Desc. Fidelidad')
                    ->disabled(),
                TextInput::make('cancellation_reason')
                    ->label('Motivo de Cancelación'),
                DateTimePicker::make('cancelled_at')
                    ->label('Fecha de Cancelación')
                    ->disabled(),
                DateTimePicker::make('confirmed_at')
                    ->label('Fecha de Confirmación')
                    ->disabled(),
                DateTimePicker::make('assigned_at')
                    ->label('Fecha de Asignación')
                    ->disabled(),
                DateTimePicker::make('delivered_at')
                    ->label('Fecha de Entrega')
                    ->disabled(),
            ]);
    }
}
