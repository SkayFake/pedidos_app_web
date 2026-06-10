<?php

namespace App\Filament\Resources\Deliverymen\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class DeliverymenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('vehicle_type')
                    ->label('Tipo Vehículo')
                    ->badge(),
                TextColumn::make('license_plate')
                    ->label('Placa')
                    ->searchable(),
                IconColumn::make('is_available')
                    ->label('Disponible')
                    ->boolean()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('active_orders_count')
                    ->label('Pedidos Activos')
                    ->numeric()
                    ->sortable(),
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
                // Acción para cambiar contraseña con verificación de admin
                Action::make('change_password')
                    ->label('Cambiar Contraseña')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        TextInput::make('admin_password')
                            ->label('Tu contraseña de administrador')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('Ingresa tu contraseña para confirmar la acción.'),
                        TextInput::make('new_password')
                            ->label('Nueva contraseña del repartidor')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirmar nueva contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('new_password'),
                    ])
                    ->action(function ($record, array $data): void {
                        $admin = auth('admin')->user();

                        if (!Hash::check($data['admin_password'], $admin->password)) {
                            Notification::make()
                                ->title('Contraseña de administrador incorrecta')
                                ->body('No se pudo verificar tu identidad.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'password' => $data['new_password'],
                        ]);

                        Notification::make()
                            ->title('Contraseña actualizada')
                            ->body("La contraseña del repartidor {$record->name} fue cambiada exitosamente.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
