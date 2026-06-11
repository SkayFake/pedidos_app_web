<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Coupon;
use App\Jobs\SendIncentiveCouponJob;

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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('send_incentive_coupon')
                        ->label('Enviar Cupón de Incentivo')
                        ->icon('heroicon-o-ticket')
                        ->form([
                            Select::make('coupon_id')
                                ->label('Cupón Plantilla')
                                ->options(Coupon::where('is_template', true)->orWhere('is_active', true)->pluck('code', 'id'))
                                ->required(),
                            Textarea::make('custom_message')
                                ->label('Mensaje personalizado (Opcional)')
                                ->default('¡Felicidades! Aquí tienes un cupón especial para tu próxima compra.')
                                ->placeholder('Escribe un mensaje aquí...'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $template = Coupon::find($data['coupon_id']);
                            if (!$template) return;
                            
                            foreach ($records as $user) {
                                SendIncentiveCouponJob::dispatch($user, $template, $data['custom_message'] ?? '');
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
