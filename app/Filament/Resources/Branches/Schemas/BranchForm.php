<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('address')
                    ->label('Dirección')
                    ->required(),
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
                TextInput::make('city')
                    ->label('Ciudad')
                    ->required(),
                TextInput::make('first_order_discount_percent')
                    ->label('% Desc. Primer Pedido')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->required(),
            ]);
    }
}
