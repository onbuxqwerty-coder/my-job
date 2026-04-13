<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\FileUpload;
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
                FileUpload::make('logo')
                    ->label('Логотип')
                    ->image()
                    ->disk('public')
                    ->directory('logos')
                    ->imagePreviewHeight('80')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                    ->maxSize(2048),
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
