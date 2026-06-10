<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Livewire\Attributes\On;

class RevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Ingresos Mensuales';

    protected ?string $description = 'Tendencia de ingresos por pedidos entregados';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '320px';

    protected string $color = 'info';

    public ?string $filter = '6_months';

    protected ?string $pollingInterval = '15s';

    #[On('order-status-changed')]
    public function refreshChart(): void
    {
        // Livewire detectará la actualización y refrescará el chart
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '6_months' => 'Últimos 6 Meses',
            'this_year' => 'Este Año',
            'last_year' => 'Año Pasado',
        ];
    }

    protected function getData(): array
    {
        $user = auth('admin')->user();
        $now = Carbon::now();

        switch ($this->filter) {
            case 'this_year':
                $startDate = $now->copy()->startOfYear();
                $endDate   = $now->copy()->endOfYear();
                break;
            case 'last_year':
                $startDate = $now->copy()->subYear()->startOfYear();
                $endDate   = $now->copy()->subYear()->endOfYear();
                break;
            default: // 6_months
                $startDate = $now->copy()->subMonths(5)->startOfMonth();
                $endDate   = $now->copy()->endOfMonth();
                break;
        }

        $query = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Filtrar por sucursal: si es super admin usa el filtro de la página; si no, usa su sucursal
        $branchId = ($user && $user->isSuperAdmin())
            ? ($this->filters['branch_id'] ?? null)
            : ($user?->branch_id ?? null);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $driver = DB::getDriverName();
        $dateSelect = match ($driver) {
            'pgsql'  => "TO_CHAR(created_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            default  => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $revenues = $query
            ->selectRaw("{$dateSelect} as month, SUM(total - (deliveryman_payout - delivery_fee)) as revenue")
            ->groupByRaw($dateSelect)
            ->orderByRaw($dateSelect)
            ->pluck('revenue', 'month')
            ->toArray();

        $labels  = [];
        $data    = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $key      = $current->format('Y-m');
            $labels[] = $current->translatedFormat('M Y');
            $data[]   = round((float) ($revenues[$key] ?? 0), 2);
            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label'            => 'Ingresos ($)',
                    'data'             => $data,
                    'borderColor'      => '#89DAD0',
                    'backgroundColor'  => 'rgba(137, 218, 208, 0.1)',
                    'tension'          => 0.4,
                    'fill'             => true,
                    'pointBackgroundColor' => '#89DAD0',
                    'pointBorderColor'     => '#fff',
                    'pointBorderWidth'     => 2,
                    'pointRadius'          => 5,
                    'pointHoverRadius'     => 7,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0,0,0,0.05)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
