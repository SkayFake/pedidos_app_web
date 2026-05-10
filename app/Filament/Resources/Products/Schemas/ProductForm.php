<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                // COLUMNA IZQUIERDA (Principal)
                Group::make()->columnSpan(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Datos del Producto')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('category_id')
                                    ->label('Categoría')
                                    ->relationship('category', 'name')
                                    ->required(),
                                Select::make('branch_id')
                                    ->label('Sucursal (Opcional, Nulo = Global)')
                                    ->relationship('branch', 'name')
                                    ->nullable(),
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                                Textarea::make('description')
                                    ->label('Descripción')
                                    ->columnSpanFull(),
                                TextInput::make('base_price')
                                    ->label('Precio Base')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('time_preparation')
                                    ->label('Tiempo de Preparación')
                                    ->placeholder('Ej: 15-20 min')
                                    ->maxLength(255),
                                TextInput::make('stars')
                                    ->label('Calificación (Estrellas)')
                                    ->numeric()
                                    ->step(0.1)
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->default(0),
                                FileUpload::make('image')
                                    ->label('Imagen')
                                    ->image()
                                    ->columnSpanFull(),
                            ]),
                        ]),

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
                        ])->columnSpan('full'),

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
                        ])->columnSpan('full'),
                ]),

                // COLUMNA DERECHA (Sidebar)
                Group::make()->columnSpan(['default' => 1, 'lg' => 1])->schema([
                    Section::make('Estado del Producto')
                        ->schema([
                            Toggle::make('is_available')
                                ->label('Disponible')
                                ->required(),
                            Toggle::make('is_recommended')
                                ->label('Recomendado')
                                ->required(),
                            Toggle::make('is_popular')
                                ->label('Popular')
                                ->required(),
                        ]),
                ]),
            ]);
    }
}
