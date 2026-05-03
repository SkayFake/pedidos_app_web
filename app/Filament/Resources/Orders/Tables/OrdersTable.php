<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Table;
use App\Models\Order;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('# Pedido')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Cliente')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'preparing' => 'primary',
                        'assigned' => 'indigo',
                        'on_way' => 'purple',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('deliveryman.name')
                    ->label('Repartidor')
                    ->sortable(),
                IconColumn::make('is_first_order_promo')
                    ->boolean(),
                IconColumn::make('is_free_delivery_promo')
                    ->boolean(),
                IconColumn::make('is_loyalty_discount')
                    ->boolean(),
                TextColumn::make('cancellation_reason')
                    ->searchable(),
                TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('confirmed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Aceptar')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->status === 'pending')
                    ->action(fn (Order $record) => $record->update(['status' => 'confirmed', 'confirmed_at' => now()])),
                Action::make('prepare')
                    ->label('A Cocina')
                    ->icon('heroicon-o-fire')
                    ->color('primary')
                    ->visible(fn (Order $record) => $record->status === 'confirmed')
                    ->action(fn (Order $record) => $record->update(['status' => 'preparing'])),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('cancellation_reason')
                            ->label('Motivo de cancelación')
                            ->required(),
                    ])
                    ->visible(fn (Order $record) => in_array($record->status, ['pending', 'confirmed', 'preparing']))
                    ->action(fn (Order $record, array $data) => $record->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => $data['cancellation_reason'],
                        'cancelled_at' => now()
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
