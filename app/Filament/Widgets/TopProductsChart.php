<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class TopProductsChart extends ChartWidget
{
    protected ?string $heading = 'Productos Más Populares';

    protected ?string $description = 'Top 5 productos por unidades vendidas';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '320px';

    protected string $color = 'warning';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $products = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->with('product:id,name')
            ->get();

        $labels = $products->map(fn($item) => $item->product?->name ?? 'Desconocido')->toArray();
        $data = $products->pluck('total_sold')->map(fn($v) => (int) $v)->toArray();

        // Ocean-inspired color palette
        $backgroundColors = [
            '#0077B6', // Ocean Blue
            '#00B4D8', // Sky Blue
            '#90E0EF', // Light Blue
            '#CAF0F8', // Pale Blue
            '#023E8A', // Deep Blue
        ];

        $hoverColors = [
            '#005F8A', // Darker Ocean
            '#009BB8', // Darker Sky
            '#6BC8D7', // Darker Light
            '#A8D8E8', // Darker Pale
            '#012F6E', // Darker Deep
        ];

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                    'hoverBackgroundColor' => array_slice($hoverColors, 0, count($data)),
                    'borderWidth' => 3,
                    'borderColor' => '#ffffff',
                    'hoverBorderColor' => '#ffffff',
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|null
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 16,
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => \Filament\Support\RawJs::make(<<<'JS'
                            function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' uds (' + percentage + '%)';
                            }
                        JS),
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
