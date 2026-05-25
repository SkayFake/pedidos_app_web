<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->rules(['email:rfc,dns'])
                    ->required()
                    ->maxLength(150),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->rules(['regex:/^\+503\s?[267]\d{7}$/'])
                    ->helperText('Formato: +503 7890 1234')
                    ->required()
                    ->maxLength(20),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->hiddenOn('edit')
                    ->minLength(8)
                    ->maxLength(255),
                DateTimePicker::make('email_verified_at')
                    ->label('Email Verificado'),
                TextInput::make('profile_photo')
                    ->label('Foto de Perfil'),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),
                TextInput::make('loyalty_points')
                    ->label('Puntos Fidelidad')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_completed_orders')
                    ->label('Pedidos Completados')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
