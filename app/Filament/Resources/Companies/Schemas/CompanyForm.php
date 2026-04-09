<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Власник')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Назва')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                TextInput::make('logo')
                    ->label('Логотип'),
                Textarea::make('description')
                    ->label('Опис')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('website')
                    ->label('Вебсайт')
                    ->url(),
                TextInput::make('location')
                    ->label('Місто')
                    ->required(),
            ]);
    }
}
