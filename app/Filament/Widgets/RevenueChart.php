<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Livewire\Attributes\On;
class RevenueChart extends ChartWidget
{
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
        // Al llamar este método, Livewire detectará la actualización y refrescará el chart
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
        $user = auth()->user();
        $now = Carbon::now();

        // Determine date range based on filter
        switch ($this->filter) {
            case 'this_year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
            case 'last_year':
                $startDate = $now->copy()->subYear()->startOfYear();
                $endDate = $now->copy()->subYear()->endOfYear();
                break;
            default: // 6_months
                $startDate = $now->copy()->subMonths(5)->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
        }

        // Build query
        $query = Order::where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate]);

        if ($user && !$user->isSuperAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        $dateSelect = match ($driver) {
            'pgsql' => "TO_CHAR(delivered_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', delivered_at)",
            default => "DATE_FORMAT(delivered_at, '%Y-%m')"
        };

        // Group by month
        $revenues = $query
            ->selectRaw("{$dateSelect} as month, SUM(total) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month')
            ->toArray();

        // Fill labels and data for every month in range
        $labels = [];
        $data = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $key = $current->format('Y-m');
            $labels[] = $current->translatedFormat('M Y');
            $data[] = round((float) ($revenues[$key] ?? 0), 2);
            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos ($)',
                    'data' => $data,
                    'borderColor' => '#0077B6',
                    'backgroundColor' => 'rgba(0, 119, 182, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                    'pointBackgroundColor' => '#0077B6',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => RawJs::make(<<<'JS'
                            function(value) {
                                return '$' + value.toLocaleString();
                            }
                        JS),
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => RawJs::make(<<<'JS'
                            function(context) {
                                return 'Ingresos: $' + context.parsed.y.toLocaleString();
                            }
                        JS),
                    ],
                ],
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
