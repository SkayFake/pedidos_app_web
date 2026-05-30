<?php

namespace App\Filament\Pages;

use App\Models\Order;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;

class OperatorDashboard extends Page
{
    protected string $view = 'filament.pages.operator-dashboard';

    protected static ?string $navigationLabel = '🚀 Panel en Tiempo Real';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $title = 'Panel de Pedidos en Tiempo Real';

    protected static string|\UnitEnum|null $navigationGroup = 'Operaciones';

    // Aparece justo antes del listado de Pedidos en el menú
    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();
        // Disponible para todos menos cocina (ellos tienen su propia vista)
        return $user && !$user->isKitchen();
    }

    public $orders;
    public int $lastOrderCount = 0;
    public bool $hasNewOrders = false;

    public function mount(): void
    {
        $this->loadOrders();
        $this->lastOrderCount = collect($this->orders)->count();
    }

    public function loadOrders(): void
    {
        $user = auth()->user();

        $query = Order::with(['user', 'branch', 'items.product', 'items.variant'])
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready_to_go', 'assigned', 'on_way'])
            ->orderByRaw("CASE
                WHEN status = 'pending'     THEN 1
                WHEN status = 'confirmed'   THEN 2
                WHEN status = 'preparing'   THEN 3
                WHEN status = 'ready_to_go' THEN 4
                WHEN status = 'assigned'    THEN 5
                WHEN status = 'on_way'      THEN 6
                ELSE 7 END ASC")
            ->orderBy('created_at', 'asc');

        if ($user && !$user->isSuperAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $this->orders = $query->get()->map(function (Order $order) {
            $order->age_seconds = now()->diffInSeconds($order->created_at);
            return $order;
        });
    }

    /** Called by wire:poll — check for genuinely new orders */
    public function pollOrders(): void
    {
        $previousCount = $this->lastOrderCount;
        $this->loadOrders();
        $newCount = collect($this->orders)->where('status', 'pending')->count();

        if ($newCount > $previousCount && $previousCount >= 0) {
            $this->hasNewOrders = true;
            $this->dispatch('new-order-arrived');
        }

        $this->lastOrderCount = $newCount;
    }

    #[On('order-status-changed')]
    public function refresh(): void
    {
        $this->loadOrders();
    }

    /** Confirm a pending order */
    public function confirmOrder(int $orderId): void
    {
        $order = Order::find($orderId);
        if (!$order || $order->status !== 'pending') return;

        $order->update(['status' => 'confirmed']);

        Notification::make()
            ->title("✅ Pedido #{$order->id} confirmado")
            ->body("El pedido de {$order->user?->name} fue confirmado.")
            ->success()
            ->send();

        $this->dispatch('order-status-changed');
        $this->loadOrders();
    }

    /** Advance order to next status */
    public function advanceOrder(int $orderId): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $flow = [
            'confirmed'   => 'preparing',
            'preparing'   => 'ready_to_go',
            'ready_to_go' => 'assigned',
            'assigned'    => 'on_way',
        ];

        $next = $flow[$order->status] ?? null;
        if (!$next) return;

        $order->update(['status' => $next]);

        $labels = [
            'preparing'   => 'En Preparación',
            'ready_to_go' => 'Listo para Enviar',
            'assigned'    => 'Asignado',
            'on_way'      => 'En Camino',
        ];

        Notification::make()
            ->title("📦 Pedido #{$order->id} → {$labels[$next]}")
            ->success()
            ->send();

        $this->dispatch('order-status-changed');
        $this->loadOrders();
    }

    /** Cancel an order with reason */
    public function cancelOrder(int $orderId, string $reason): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $order->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        Notification::make()
            ->title("❌ Pedido #{$order->id} cancelado")
            ->body("Motivo: {$reason}")
            ->danger()
            ->send();

        $this->dispatch('order-status-changed');
        $this->loadOrders();
    }

    public function clearNewOrderAlert(): void
    {
        $this->hasNewOrders = false;
    }

    protected function getViewData(): array
    {
        return ['orders' => $this->orders];
    }
}
