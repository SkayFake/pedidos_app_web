<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Ingresos por Sucursal';

    protected ?string $description = 'Comparativa de ingresos totales históricos';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $branches = Branch::all();

        $revenues = Order::where('status', 'delivered')
            ->select('branch_id', DB::raw('SUM(total) as revenue'))
            ->groupBy('branch_id')
            ->pluck('revenue', 'branch_id')
            ->toArray();

        $labels = [];
        $data = [];
        $colors = [];
        $palette = ['#0077B6', '#00B4D8', '#90E0EF', '#023E8A', '#48CAE4', '#ADE8F4', '#03045E', '#0096C7'];

        foreach ($branches as $index => $branch) {
            $labels[] = $branch->name;
            $data[] = round((float) ($revenues[$branch->id] ?? 0), 2);
            $colors[] = $palette[$index % count($palette)];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos ($)',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(255,255,255,0.05)',
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
