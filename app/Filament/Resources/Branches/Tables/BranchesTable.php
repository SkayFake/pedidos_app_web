<?php

namespace App\Filament\Resources\Branches\Tables;

use App\Services\BranchScheduleService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth('admin')->user();
                if ($user && $user->isBranchAdmin()) {
                    $query->where('id', $user->branch_id);
                }
                $query->with(['schedules', 'specialSchedules']);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable(),
                TextColumn::make('first_order_discount_percent')
                    ->label('% Desc. Primer Pedido')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('is_open_now')
                    ->label('Estado Actual')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        $service = app(BranchScheduleService::class);
                        $availability = $service->checkAvailability($record);
                        return $availability['is_open'] ? 'Abierto' : 'Cerrado';
                    })
                    ->color(fn (string $state): string => $state === 'Abierto' ? 'success' : 'danger'),
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
