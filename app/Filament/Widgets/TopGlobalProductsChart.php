<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class TopGlobalProductsChart extends ChartWidget
{
    protected ?string $heading = 'Productos Estrella Globales';

    protected ?string $description = 'Top 5 productos más vendidos en toda la red';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '320px';

    protected string $color = 'warning';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $products = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('order', function($q) {
                $q->where('status', 'delivered');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->with('product:id,name')
            ->get();

        $labels = $products->map(fn($item) => $item->product?->name ?? 'Desconocido')->toArray();
        $data = $products->pluck('total_sold')->map(fn($v) => (int) $v)->toArray();

        $backgroundColors = [
            '#F59E0B', '#10B981', '#3B82F6', '#6366F1', '#EC4899'
        ];

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
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
                    'position' => 'right',
                ],
            ],
            'cutout' => '65%',
        ];
    }
}
