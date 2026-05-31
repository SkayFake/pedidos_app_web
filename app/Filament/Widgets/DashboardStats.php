<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DashboardStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    /**
     * Polling de respaldo: refresca automáticamente cada 15 segundos
     * por si el evento de Livewire no se despacha correctamente.
     */
    protected ?string $pollingInterval = '15s';

    /**
     * Listener reactivo: cuando se despacha el evento 'order-status-changed'
     * desde cualquier parte del sistema, este método fuerza la actualización
     * de todas las tarjetas de estadísticas sin recargar la página.
     */
    #[On('order-status-changed')]
    public function refreshStats(): void
    {
        // Simplemente con llamar a $this->getStats() internamente,
        // Filament re-renderiza el widget porque Livewire detecta
        // que el componente se ha "tocado".
        // No se necesita lógica adicional aquí.
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? ($this->filters['branch_id'] ?? null) : $user->branch_id;

        // Base query respecting branch access
        $ordersQuery = Order::where('status', 'delivered');
        if ($branchId) {
            $ordersQuery->where('branch_id', $branchId);
        }

        // ── Contadores de Pedidos por Estado ──────────────────────
        $totalOrders = Order::when(
            $branchId,
            fn($q) => $q->where('branch_id', $branchId)
        )->count();

        $deliveredCount = (clone $ordersQuery)->count();
        $pendingCount = Order::where('status', 'pending')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();
        $cancelledCount = Order::where('status', 'cancelled')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();

        // ── Producto Más Vendido ─────────────────────────────────
        $topProduct = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->when($branchId, function($q) use ($branchId) {
                $q->whereHas('order', function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->with('product:id,name')
            ->first();

        $topProductName = $topProduct?->product?->name ?? 'Sin datos';
        $topProductQty = $topProduct?->total_sold ?? 0;

        // ── Producto Menos Vendido ───────────────────────────────
        $leastProduct = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->when($branchId, function($q) use ($branchId) {
                $q->whereHas('order', function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
            })
            ->groupBy('product_id')
            ->orderBy('total_sold', 'asc')
            ->with('product:id,name')
            ->first();

        $leastProductName = $leastProduct?->product?->name ?? 'Sin datos';
        $leastProductQty = $leastProduct?->total_sold ?? 0;

        // ── Ganancias por Período ────────────────────────────────
        $today = Carbon::today();

        $dailyRevenue = (clone $ordersQuery)
            ->whereDate('delivered_at', $today)
            ->sum('total');

        $weeklyRevenue = (clone $ordersQuery)
            ->whereBetween('delivered_at', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()])
            ->sum('total');

        $monthlyRevenue = (clone $ordersQuery)
            ->whereMonth('delivered_at', $today->month)
            ->whereYear('delivered_at', $today->year)
            ->sum('total');

        $yearlyRevenue = (clone $ordersQuery)
            ->whereYear('delivered_at', $today->year)
            ->sum('total');

        // ── Sparkline: últimos 7 días de ganancias ───────────────
        $dailySparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dailySparkline[] = (float) Order::where('status', 'delivered')
                ->whereDate('delivered_at', $date)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->sum('total');
        }

        // ── Sparkline: últimas 4 semanas ─────────────────────────
        $weeklySparkline = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = $today->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $today->copy()->subWeeks($i)->endOfWeek();
            $weeklySparkline[] = (float) Order::where('status', 'delivered')
                ->whereBetween('delivered_at', [$weekStart, $weekEnd])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->sum('total');
        }

        // ── Sparkline: últimos 6 meses ───────────────────────────
        $monthlySparkline = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $monthlySparkline[] = (float) Order::where('status', 'delivered')
                ->whereMonth('delivered_at', $month->month)
                ->whereYear('delivered_at', $month->year)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->sum('total');
        }

        return [
            Stat::make('Total Pedidos 📋', $totalOrders)
                ->description("✅ {$deliveredCount} entregados · ⏳ {$pendingCount} pendientes · ❌ {$cancelledCount} cancelados")
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->chart([$pendingCount, $deliveredCount, $cancelledCount])
                ->chartColor('primary'),

            Stat::make('Producto Estrella 🏆', $topProductName)
                ->description("{$topProductQty} unidades vendidas")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([3, 5, 8, 12, 8, 15, (int)$topProductQty])
                ->chartColor('success'),

            Stat::make('Menor Rotación 📉', $leastProductName)
                ->description("{$leastProductQty} unidades vendidas")
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([10, 8, 6, 4, 3, 2, (int)$leastProductQty])
                ->chartColor('danger'),

            Stat::make('Ganancias Hoy 💰', '$' . number_format($dailyRevenue, 2))
                ->description($dailyRevenue > 0 ? 'Ingreso del día' : 'Sin ventas hoy')
                ->descriptionIcon($dailyRevenue > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->color($dailyRevenue > 0 ? 'success' : 'gray')
                ->chart($dailySparkline)
                ->chartColor($dailyRevenue > 0 ? 'success' : 'gray'),

            Stat::make('Ganancias Semana 📅', '$' . number_format($weeklyRevenue, 2))
                ->description('Semana actual')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($weeklyRevenue > 100 ? 'success' : ($weeklyRevenue > 0 ? 'warning' : 'gray'))
                ->chart($weeklySparkline)
                ->chartColor($weeklyRevenue > 0 ? 'success' : 'gray'),

            Stat::make('Ganancias Mes 📆', '$' . number_format($monthlyRevenue, 2))
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($monthlyRevenue > 500 ? 'success' : ($monthlyRevenue > 0 ? 'warning' : 'gray'))
                ->chart($monthlySparkline)
                ->chartColor($monthlyRevenue > 0 ? 'success' : 'gray'),

            Stat::make('Ganancias Año 📊', '$' . number_format($yearlyRevenue, 2))
                ->description('Acumulado ' . $today->year)
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($yearlyRevenue > 5000 ? 'success' : ($yearlyRevenue > 0 ? 'info' : 'gray'))
                ->chart($monthlySparkline)
                ->chartColor($yearlyRevenue > 0 ? 'info' : 'gray'),
        ];
    }
}
