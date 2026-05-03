<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('base_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                FileUpload::make('image')
                    ->image(),
                Toggle::make('is_available')
                    ->required(),
                Toggle::make('is_recommended')
                    ->required(),
                Toggle::make('is_popular')
                    ->required(),

                Section::make('Variantes del Producto')
                    ->description('Ej. Tamaños, sin cebolla, etc.')
                    ->schema([
                        Repeater::make('variants')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Nombre de Variante'),
                                TextInput::make('price_modifier')
                                    ->numeric()
                                    ->default(0.00)
                                    ->label('Modificador de Precio (+ o -)'),
                                Toggle::make('is_default')
                                    ->label('Es por defecto'),
                                Toggle::make('is_available')
                                    ->default(true)
                                    ->label('Disponible'),
                            ])
                            ->columns(2)
                    ]),

                Section::make('Extras Adicionales')
                    ->description('Ej. Queso extra, doble carne')
                    ->schema([
                        Repeater::make('extras')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Nombre del Extra'),
                                TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->label('Precio'),
                                Toggle::make('is_available')
                                    ->default(true)
                                    ->label('Disponible'),
                            ])
                            ->columns(3)
                    ]),
            ]);
    }
}
