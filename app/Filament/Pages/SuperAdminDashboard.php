<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use App\Filament\Widgets\SystemOverviewStats;
use App\Filament\Widgets\BranchRevenueChart;
use App\Filament\Widgets\TopGlobalProductsChart;
use App\Filament\Widgets\BranchPerformanceTable;

class SuperAdminDashboard extends BaseDashboard
{
    // Override the static route path to prevent conflict with default '/' dashboard
    protected static string $routePath = 'metricas-globales';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-globe-americas';
    protected static ?string $navigationLabel = 'Métricas Globales';
    protected static ?string $title = 'Métricas Globales (Red)';
    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();
        return $user && $user->isSuperAdmin();
    }

    public function getWidgets(): array
    {
        return [
            SystemOverviewStats::class,
            BranchRevenueChart::class,
            TopGlobalProductsChart::class,
            BranchPerformanceTable::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}
