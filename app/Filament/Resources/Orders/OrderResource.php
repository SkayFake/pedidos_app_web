<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    // Icono de portapapeles para pedidos
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    // Traducción en el menú lateral
    protected static ?string $navigationLabel = 'Pedidos';

    // Traducción para los botones (Ej: "Crear Pedido")
    protected static ?string $modelLabel = 'Pedido';
    protected static ?string $pluralModelLabel = 'Pedidos';

    // Agrupación en el menú lateral
    protected static string | \UnitEnum | null $navigationGroup = 'Operaciones';

    protected static ?string $recordTitleAttribute = 'id';

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();
        // Kitchen users use KitchenDisplay instead
        return $user && !$user->isKitchen();
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        // 1. Filtrar solo pedidos activos (excluir cancelados y entregados)
        $query->whereNotIn('status', ['cancelled', 'delivered']);

        // 2. Ordenar por estado (flujo lógico)
        $query->orderByRaw("CASE 
            WHEN status = 'pending' THEN 1 
            WHEN status = 'confirmed' THEN 2 
            WHEN status = 'preparing' THEN 3 
            WHEN status = 'ready_to_go' THEN 4 
            WHEN status = 'assigned' THEN 5 
            WHEN status = 'on_way' THEN 6 
            ELSE 7 END ASC")
            ->orderBy('created_at', 'desc');

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
