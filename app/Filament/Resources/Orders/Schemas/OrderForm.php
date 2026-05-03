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
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->disabled(),
                Select::make('deliveryman_id')
                    ->relationship('deliveryman', 'name')
                    ->searchable(),
                Select::make('address_id')
                    ->relationship('address', 'street')
                    ->required()
                    ->disabled(),
                Select::make('coupon_id')
                    ->relationship('coupon', 'code')
                    ->disabled(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'assigned' => 'Assigned',
            'on_way' => 'On way',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ])
                    ->default('pending')
                    ->required(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->disabled(),
                TextInput::make('delivery_fee')
                    ->required()
                    ->numeric()
                    ->disabled(),
                TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->disabled(),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->disabled(),
                Toggle::make('is_first_order_promo')
                    ->disabled(),
                Toggle::make('is_free_delivery_promo')
                    ->disabled(),
                Toggle::make('is_loyalty_discount')
                    ->disabled(),
                TextInput::make('cancellation_reason'),
                DateTimePicker::make('cancelled_at')
                    ->disabled(),
                DateTimePicker::make('confirmed_at')
                    ->disabled(),
                DateTimePicker::make('assigned_at')
                    ->disabled(),
                DateTimePicker::make('delivered_at')
                    ->disabled(),
            ]);
    }
}
