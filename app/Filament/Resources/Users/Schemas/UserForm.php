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
                    ->required(),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->required(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required(),
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
