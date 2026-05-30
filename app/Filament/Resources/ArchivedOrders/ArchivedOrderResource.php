<?php

namespace App\Filament\Resources\ArchivedOrders;

use App\Filament\Resources\ArchivedOrders\Pages\ListArchivedOrders;
use App\Filament\Resources\ArchivedOrders\Pages\ViewArchivedOrder;
use App\Models\ArchivedOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ArchivedOrderResource extends Resource
{
    protected static ?string $model = ArchivedOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Archivo de Pedidos';
    protected static ?string $modelLabel = 'Pedido Archivado';
    protected static ?string $pluralModelLabel = 'Pedidos Archivados';

    protected static string | \UnitEnum | null $navigationGroup = 'Operaciones';
    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'id';

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();
        return $user && !$user->isOperator() && !$user->isKitchen();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('# Pedido')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('deliveryman.name')
                    ->label('Repartidor'),
                TextColumn::make('cancellation_reason')
                    ->label('Motivo Cancelación')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('delivered_at')
                    ->label('Fecha Entrega')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancelled_at')
                    ->label('Fecha Cancelación')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha Pedido')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth('admin')->user();

        // Los admins de sucursal solo ven pedidos archivados de su sucursal
        if ($user && !$user->isSuperAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArchivedOrders::route('/'),
            'view' => ViewArchivedOrder::route('/{record}'),
        ];
    }
}
