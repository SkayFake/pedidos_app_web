<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\User;
use App\Models\Deliveryman;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        
        $ordersQuery = Order::query();
        if ($user && !$user->isSuperAdmin() && $user->branch_id) {
            $ordersQuery->where('branch_id', $user->branch_id);
        }

        $todayOrders = (clone $ordersQuery)->whereDate('created_at', today())->count();
        $todaySales = (clone $ordersQuery)->whereDate('created_at', today())->where('status', 'delivered')->sum('total');
        $activeDeliverymen = Deliveryman::where('is_active', true)->where('active_orders_count', '>', 0)->count();

        return [
            Stat::make('Pedidos de Hoy', $todayOrders)
                ->description('Nuevos pedidos')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
            Stat::make('Ventas de Hoy', '$' . number_format($todaySales, 2))
                ->description('Ingresos confirmados')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            Stat::make('Repartidores Activos', $activeDeliverymen)
                ->description('Entregando pedidos actualmente')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
        ];
    }
}
