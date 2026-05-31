<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Coupons\Schemas\CouponForm;
use App\Filament\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    // Icono de ticket para cupones
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    // Traducción en el menú lateral
    protected static ?string $navigationLabel = 'Cupones';

    // Traducción para los botones (Ej: "Crear Cupón")
    protected static ?string $modelLabel = 'Cupón';
    protected static ?string $pluralModelLabel = 'Cupones';

    // Agrupación en el menú lateral
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogo';

    protected static ?string $recordTitleAttribute = 'code';

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();
        return $user && !$user->isOperator() && !$user->isKitchen();
    }

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth('admin')->user();

        // Los admins de sucursal solo ven cupones de su sucursal
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
            'index' => ListCoupons::route('/'),
        ];
    }
}
