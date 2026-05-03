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
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('profile_photo'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('loyalty_points')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_completed_orders')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
