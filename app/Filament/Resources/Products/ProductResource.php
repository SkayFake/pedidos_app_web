<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    // 1. Cambiamos el icono a una bolsa de compras
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    // 2. Traducción en el menú lateral
    protected static ?string $navigationLabel = 'Productos';

    // 3. Traducción para los botones (Ej: "Crear Producto")
    protected static ?string $modelLabel = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';

    // 4. Agrupación en el menú lateral
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogo';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();
        return $user && !$user->isOperator() && !$user->isKitchen();
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth('admin')->user();

        // Los admins de sucursal solo ven productos de su sucursal
        if ($user && !$user->isSuperAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}