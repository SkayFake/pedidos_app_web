<?php

namespace App\Filament\Resources\Users\Tables;

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

class UsersTable
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
                TextColumn::make('email_verified_at')
                    ->label('Email Verificado')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('profile_photo')
                    ->label('Foto Perfil')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('loyalty_points')
                    ->label('Puntos Fidelidad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_completed_orders')
                    ->label('Pedidos Completados')
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
                            ->label('Nueva contraseña del cliente')
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
                        // Verificar contraseña del admin en sesión
                        $admin = auth('admin')->user();

                        if (!Hash::check($data['admin_password'], $admin->password)) {
                            Notification::make()
                                ->title('Contraseña de administrador incorrecta')
                                ->body('No se pudo verificar tu identidad.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Actualizar contraseña del cliente
                        $record->update([
                            'password' => $data['new_password'], // Se hashea automáticamente por el cast
                        ]);

                        Notification::make()
                            ->title('Contraseña actualizada')
                            ->body("La contraseña del cliente {$record->name} fue cambiada exitosamente.")
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
