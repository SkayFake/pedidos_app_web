<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Models\Order;

class OrdersTable
{
    /**
     * Mapa de estados en orden lógico de flujo.
     * El valor numérico define la posición en el flujo del pedido.
    private static array $statusOrder = [
        'pending'     => 0,
        'confirmed'   => 1,
        'preparing'   => 2,
        'ready_to_go' => 3,
        'assigned'    => 4,
        'on_way'      => 5,
        'delivered'   => 6,
        'cancelled'   => 7,
    ];

    /**
     * Labels en español para cada estado.
     */
    private static array $statusLabels = [
        'pending'     => 'Pendiente',
        'confirmed'   => 'Confirmado',
        'preparing'   => 'En Preparación',
        'ready_to_go' => 'Listo para Enviar',
        'assigned'    => 'Asignado',
        'on_way'      => 'En Camino',
        'delivered'   => 'Entregado',
        'cancelled'   => 'Cancelado',
    ];

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

                // ── SelectColumn Dinámico de Estado ──────────────────
                SelectColumn::make('status')
                    ->label('Estado')
                    ->options(self::$statusLabels)
                    ->selectablePlaceholder(false)
                    ->disableOptionWhen(function (string $value, Order $record): bool {
                        $currentStatus = $record->status;
                        $currentIndex = self::$statusOrder[$currentStatus] ?? 0;
                        $optionIndex = self::$statusOrder[$value] ?? 0;

                        // Si ya está cancelado, bloquear TODAS las opciones
                        if ($currentStatus === 'cancelled') {
                            return true;
                        }

                        // Si ya está entregado, bloquear TODAS las opciones
                        if ($currentStatus === 'delivered') {
                            return true;
                        }

                        // Bloquear retroceder a un estado anterior
                        if ($optionIndex < $currentIndex) {
                            return true;
                        }

                        // Permitir solo avanzar al estado inmediatamente siguiente
                        // o cancelar (excepto si ya fue entregado)
                        if ($value === 'cancelled') {
                            return false; // Siempre se puede cancelar (si no está entregado)
                        }

                        // Bloquear saltar estados (solo se puede avanzar de uno en uno)
                        if ($optionIndex > $currentIndex + 1) {
                            return true;
                        }

                        return false;
                    })
                    ->afterStateUpdated(function (Order $record, string $state, $livewire): void {
                        // Actualizar timestamps según el nuevo estado
                        $updates = match ($state) {
                            'confirmed'   => ['confirmed_at' => now()],
                            'ready_to_go' => ['assigned_at' => now()],
                            'assigned'    => ['assigned_at' => now()],
                            'delivered'   => ['delivered_at' => now()],
                            'cancelled'   => ['cancelled_at' => now()],
                            default       => [],
                        };

                        if (!empty($updates)) {
                            $record->update($updates);
                        }

                        // Notificación visual al usuario
                        $label = self::$statusLabels[$state] ?? $state;
                        Notification::make()
                            ->title("Pedido #{$record->id} actualizado")
                            ->body("Nuevo estado: {$label}")
                            ->success()
                            ->send();

                        // Despachar evento global para que los widgets se actualicen
                        $livewire->dispatch('order-status-changed');
                    }),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('deliveryman.name')
                    ->label('Repartidor')
                    ->sortable(),
                IconColumn::make('is_first_order_promo')
                    ->label('Promo 1er Pedido')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_free_delivery_promo')
                    ->label('Envío Gratis')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_loyalty_discount')
                    ->label('Desc. Fidelidad')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cancellation_reason')
                    ->label('Motivo Cancelación')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cancelled_at')
                    ->label('Fecha Cancelación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('confirmed_at')
                    ->label('Fecha Confirmación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assigned_at')
                    ->label('Fecha Asignación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('delivered_at')
                    ->label('Fecha Entrega')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->poll('5s')
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
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
                    ->visible(fn (Order $record) => !in_array($record->status, ['delivered', 'cancelled']))
                    ->action(function (Order $record, array $data, $livewire): void {
                        $record->update([
                            'status' => 'cancelled',
                            'cancellation_reason' => $data['cancellation_reason'],
                            'cancelled_at' => now(),
                        ]);

                        Notification::make()
                            ->title("Pedido #{$record->id} cancelado")
                            ->body("Motivo: {$data['cancellation_reason']}")
                            ->danger()
                            ->send();

                        $livewire->dispatch('order-status-changed');
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('cancel_bulk')
                        ->label('Cancelar y Archivar Seleccionados')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\TextInput::make('cancellation_reason')
                                ->label('Motivo de cancelación')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, $livewire): void {
                            foreach ($records as $record) {
                                if (!in_array($record->status, ['delivered', 'cancelled'])) {
                                    $record->update([
                                        'status' => 'cancelled',
                                        'cancellation_reason' => $data['cancellation_reason'],
                                        'cancelled_at' => now(),
                                    ]);
                                }
                            }
                            Notification::make()
                                ->title('Pedidos cancelados y archivados')
                                ->success()
                                ->send();
                                
                            $livewire->dispatch('order-status-changed');
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
