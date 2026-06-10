<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TopProductsChart extends ChartWidget
{
    use InteractsWithPageFilters;

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
        $user = auth('admin')->user();
        $branchId = ($user && $user->isSuperAdmin()) ? ($this->filters['branch_id'] ?? null) : ($user?->branch_id ?? null);

        $products = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->when($branchId, function($q) use ($branchId) {
                $q->whereHas('order', function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->with('product:id,name')
            ->get();

        $labels = $products->map(fn($item) => $item->product?->name ?? 'Desconocido')->toArray();
        $data = $products->pluck('total_sold')->map(fn($v) => (int) $v)->toArray();

        // Ocean-inspired color palette
        $backgroundColors = [
            '#89DAD0', // Mint Primary
            '#69C5DF', // Sky Info
            '#B2EBE3', // Mint Light
            '#E0F7F4', // Mint Pale
            '#2F877E', // Deep Teal
        ];

        $hoverColors = [
            '#6FC9BE', // Darker Mint
            '#4FB1CB', // Darker Sky
            '#98DED5', // Darker Light
            '#C5ECE7', // Darker Pale
            '#226F67', // Darker Deep Teal
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
            ],
            'cutout' => '60%',
        ];
    }
}
