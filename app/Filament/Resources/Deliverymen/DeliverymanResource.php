<?php

namespace App\Filament\Resources\Deliverymen;

use App\Filament\Resources\Deliverymen\Pages\CreateDeliveryman;
use App\Filament\Resources\Deliverymen\Pages\EditDeliveryman;
use App\Filament\Resources\Deliverymen\Pages\ListDeliverymen;
use App\Filament\Resources\Deliverymen\Schemas\DeliverymanForm;
use App\Filament\Resources\Deliverymen\Tables\DeliverymenTable;
use App\Models\Deliveryman;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeliverymanResource extends Resource
{
    protected static ?string $model = Deliveryman::class;

    // Icono de camión para repartidores
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    // Traducción en el menú lateral
    protected static ?string $navigationLabel = 'Repartidores';

    // Traducción para los botones (Ej: "Crear Repartidor")
    protected static ?string $modelLabel = 'Repartidor';
    protected static ?string $pluralModelLabel = 'Repartidores';

    // Agrupación en el menú lateral
    protected static string | \UnitEnum | null $navigationGroup = 'Operaciones';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();
        return $user && $user->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return DeliverymanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliverymenTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliverymen::route('/'),
        ];
    }
}
