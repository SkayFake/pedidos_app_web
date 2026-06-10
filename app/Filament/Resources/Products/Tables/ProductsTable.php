<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('stars')
                    ->label('Estrellas')
                    ->numeric(1)
                    ->icon('heroicon-s-star')
                    ->iconColor('warning')
                    ->sortable(),
                TextColumn::make('base_price')
                    ->label('Precio Base')
                    ->money()
                    ->sortable(),
                ImageColumn::make('image')
                    ->label('Imagen')
                    ->disk('public'),
                IconColumn::make('is_available')
                    ->label('Disponible')
                    ->boolean()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                IconColumn::make('is_recommended')
                    ->label('Recomendado')
                    ->boolean()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
