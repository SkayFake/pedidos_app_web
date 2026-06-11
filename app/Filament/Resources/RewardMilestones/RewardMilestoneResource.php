<?php

namespace App\Filament\Resources\RewardMilestones;

use App\Filament\Resources\RewardMilestones\Pages\ManageRewardMilestones;
use App\Models\RewardMilestone;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RewardMilestoneResource extends Resource
{
    protected static ?string $model = RewardMilestone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;
    protected static ?string $navigationLabel = 'Hitos de Fidelidad';
    protected static ?string $modelLabel = 'Hito';
    protected static ?string $pluralModelLabel = 'Hitos de Fidelidad';
    protected static string | \UnitEnum | null $navigationGroup = 'Marketing';

    protected static ?string $recordTitleAttribute = 'points_required';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('points_required')
                    ->label('Puntos Requeridos')
                    ->required()
                    ->numeric()
                    ->helperText('Cantidad de puntos históricos para ganar este premio.'),
                Select::make('coupon_id')
                    ->label('Cupón Plantilla')
                    ->relationship('coupon', 'code', fn ($query) => $query->where('is_template', true)->orWhere('is_active', true))
                    ->required(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('points_required')
            ->columns([
                TextColumn::make('points_required')
                    ->label('Puntos')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('coupon.code')
                    ->label('Cupón')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRewardMilestones::route('/'),
        ];
    }
}
