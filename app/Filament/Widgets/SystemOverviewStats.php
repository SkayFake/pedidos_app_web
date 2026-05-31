<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\Branch;
use App\Models\AdminUser;
use Carbon\Carbon;

class SystemOverviewStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();

        $activeBranches = Branch::count();
        
        $totalRevenue = Order::where('status', 'delivered')->sum('total');
        $todayRevenue = Order::where('status', 'delivered')->whereDate('delivered_at', $today)->sum('total');
        
        $totalOrders = Order::count();
        $totalUsers = AdminUser::count();

        return [
            Stat::make('Total Sucursales', $activeBranches)
                ->description('Sucursales operativas')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),

            Stat::make('Ingreso Histórico Global', '$' . number_format($totalRevenue, 2))
                ->description('Ingresos de hoy: $' . number_format($todayRevenue, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total de Pedidos', number_format($totalOrders))
                ->description('Todas las sucursales')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Empleados / Usuarios', $totalUsers)
                ->description('Personal registrado')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}
