<?php

namespace App\Filament\Pages;

use App\Models\Order;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;

class KitchenDisplay extends Page
{
    // Filament 4: $view es property de instancia en BasePage, la asignamos aquí
    protected string $view = 'filament.pages.kitchen-display';

    protected static ?string $navigationLabel = 'Pantalla de Cocina';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?string $title = 'Pantalla de Cocina';

    protected static string | \UnitEnum | null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 10;

    // Auto-refresco cada 10 segundos vía Livewire polling
    protected string $pollingInterval = '10s';

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
    }

    public $orders;

    public function mount(): void
    {
        $this->loadOrders();
    }

    public function loadOrders(): void
    {
        $user = auth()->user();

        $query = Order::with([
            'items.product',
            'items.variant',
            'items.extras.extra',
        ])
            ->whereIn('status', ['confirmed', 'preparing'])
            ->orderBy('confirmed_at', 'asc');

        if ($user && !$user->isSuperAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $this->orders = $query->get()->map(function (Order $order) {
            $startTime = $order->confirmed_at ?? $order->created_at;
            $order->start_timestamp = $startTime?->timestamp;
            return $order;
        });
    }

    #[On('refresh-kitchen')]
    #[On('refresh-page')]
    public function refresh(): void
    {
        $this->loadOrders();
    }

    public function markAsReady(int $orderId): void
    {
        $order = Order::find($orderId);

        if (!$order) {
            Notification::make()
                ->title('Pedido no encontrado')
                ->danger()
                ->send();
            return;
        }

        if (!in_array($order->status, ['confirmed', 'preparing'])) {
            Notification::make()
                ->title('El pedido ya fue procesado')
                ->warning()
                ->send();
            return;
        }

        $order->update([
            'status'      => 'ready_to_go',
            'assigned_at' => now(),
        ]);

        Notification::make()
            ->title("✅ Pedido #{$order->id} listo para enviar")
            ->success()
            ->send();

        $this->dispatch('order-status-changed');
        $this->loadOrders();
    }

    protected function getViewData(): array
    {
        return [
            'orders' => $this->orders,
        ];
    }
}
