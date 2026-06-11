<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth('admin')->user();
        $isBranchAdmin = $user && $user->isBranchAdmin();

        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                // ── COLUMNA IZQUIERDA: Información General ──────────────────────
                Group::make()
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->schema([
                        Section::make('Información General')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->disabled($isBranchAdmin),
                                TextInput::make('address')
                                    ->label('Dirección')
                                    ->required()
                                    ->disabled($isBranchAdmin),
                                TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->prefix('+503 ')
                                    ->rules(['regex:/^[2567]\d{7}$/'])
                                    ->validationMessages([
                                        'regex' => 'El teléfono debe tener 8 dígitos y comenzar con 2, 5, 6 o 7.',
                                    ])
                                    ->required()
                                    ->maxLength(8)
                                    ->minLength(8)
                                    ->formatStateUsing(fn ($state) => $state ? preg_replace('/^\+503\s*/', '', $state) : null)
                                    ->dehydrateStateUsing(fn ($state) => $state ? '+503' . $state : null)
                                    ->extraInputAttributes([
                                        'autocomplete' => 'off',
                                        'x-on:input' => "\$el.value = \$el.value.replace(/\D/g, ''); if (\$el.value.length > 0 && !/^[2567]/.test(\$el.value)) { \$el.value = ''; } if (\$el.value.length > 8) { \$el.value = \$el.value.substring(0, 8); } \$el.dispatchEvent(new Event('input'))",
                                    ])
                                    ->disabled($isBranchAdmin),
                                TextInput::make('city')
                                    ->label('Ciudad')
                                    ->required()
                                    ->disabled($isBranchAdmin),
                                TextInput::make('first_order_discount_percent')
                                    ->label('% Desc. Primer Pedido')
                                    ->required()
                                    ->numeric()
                                    ->default(0.0)
                                    ->disabled($isBranchAdmin),
                                Toggle::make('is_active')
                                    ->label('Activa')
                                    ->required()
                                    ->disabled($isBranchAdmin),
                            ])
                            ->columns(2),
                    ]),

                // ── COLUMNA DERECHA: Horarios ───────────────────────────────────
                Group::make()
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        // ── Horario Regular ──────────────────────────
                        Section::make('Horario Regular')
                            ->description('Define el horario semanal. Puedes agregar múltiples turnos por día.')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Repeater::make('schedules')
                                    ->label('')
                                    ->relationship('schedules')
                                    ->schema([
                                        Select::make('day_of_week')
                                            ->label('Día')
                                            ->options([
                                                0 => 'Domingo',
                                                1 => 'Lunes',
                                                2 => 'Martes',
                                                3 => 'Miércoles',
                                                4 => 'Jueves',
                                                5 => 'Viernes',
                                                6 => 'Sábado',
                                            ])
                                            ->required(),
                                        Toggle::make('is_closed')
                                            ->label('Cerrado este día')
                                            ->default(false)
                                            ->inline(false)
                                            ->live(),
                                        TimePicker::make('open_time')
                                            ->label('Hora de apertura')
                                            ->seconds(false)
                                            ->required(fn ($get) => !$get('is_closed'))
                                            ->hidden(fn ($get) => $get('is_closed')),
                                        TimePicker::make('close_time')
                                            ->label('Hora de cierre')
                                            ->seconds(false)
                                            ->required(fn ($get) => !$get('is_closed'))
                                            ->hidden(fn ($get) => $get('is_closed')),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->addActionLabel('Agregar turno')
                                    ->reorderable(false)
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string =>
                                        isset($state['day_of_week'])
                                            ? [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'][$state['day_of_week']] ?? null
                                            : null
                                    ),
                            ])
                            ->collapsible(),

                        // ── Horarios Especiales ──────────────────────
                        Section::make('Horarios Especiales')
                            ->description('Define horarios para fechas específicas (feriados, eventos).')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Repeater::make('specialSchedules')
                                    ->label('')
                                    ->relationship('specialSchedules')
                                    ->schema([
                                        DatePicker::make('date')
                                            ->label('Fecha')
                                            ->required(),
                                        TextInput::make('label')
                                            ->label('Motivo')
                                            ->placeholder('Ej: Navidad, Año Nuevo')
                                            ->maxLength(100),
                                        Toggle::make('is_closed')
                                            ->label('Cerrado')
                                            ->default(false)
                                            ->inline(false)
                                            ->columnSpan('full')
                                            ->live(),
                                        TimePicker::make('open_time')
                                            ->label('Hora de apertura')
                                            ->seconds(false)
                                            ->required(fn ($get) => !$get('is_closed'))
                                            ->hidden(fn ($get) => $get('is_closed')),
                                        TimePicker::make('close_time')
                                            ->label('Hora de cierre')
                                            ->seconds(false)
                                            ->required(fn ($get) => !$get('is_closed'))
                                            ->hidden(fn ($get) => $get('is_closed')),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->addActionLabel('Agregar horario especial')
                                    ->reorderable(false)
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string =>
                                        isset($state['date'])
                                            ? ($state['date'] . ($state['label'] ?? '' ? ' - ' . $state['label'] : ''))
                                            : null
                                    ),
                            ])
                            ->collapsible()
                            ->collapsed(),
                    ]),
            ]);
    }
}
