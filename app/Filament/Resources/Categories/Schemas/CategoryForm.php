<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Батьківська категорія')
                    ->options(fn () => Category::whereNull('parent_id')->pluck('name', 'id'))
                    ->placeholder('— Без батьківської —')
                    ->nullable()
                    ->searchable(),
                TextInput::make('name')
                    ->label('Назва')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                TextInput::make('icon')
                    ->label('Іконка'),
            ]);
    }
}
