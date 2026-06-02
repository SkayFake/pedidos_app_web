<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BranchPerformanceTable extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Ranking de Sucursales';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Branch::query()
                    ->withCount(['orders as completed_orders' => function ($query) {
                        $query->where('status', 'delivered');
                    }])
                    ->withSum(['orders as total_revenue' => function ($query) {
                        $query->where('status', 'delivered');
                    }], \Illuminate\Support\Facades\DB::raw('total - (deliveryman_payout - delivery_fee)'))
            )
            ->defaultSort('total_revenue', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('completed_orders')
                    ->label('Pedidos Completados')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Ingresos Totales')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('average_ticket')
                    ->label('Ticket Promedio')
                    ->state(function (Branch $record): float {
                        if ($record->completed_orders > 0) {
                            return $record->total_revenue / $record->completed_orders;
                        }
                        return 0;
                    })
                    ->money('USD')
                    ->color('gray'),
            ]);
    }
}
