<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Enums\EmploymentType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VacancyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Компанія')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('category_id')
                    ->label('Категорія')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('title')
                    ->label('Назва вакансії')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                Textarea::make('description')
                    ->label('Опис')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('salary_from')
                    ->label('Зарплата від')
                    ->numeric(),
                TextInput::make('salary_to')
                    ->label('Зарплата до')
                    ->numeric(),
                TextInput::make('currency')
                    ->label('Валюта')
                    ->required()
                    ->default('UAH'),
                Select::make('employment_type')
                    ->label('Тип зайнятості')
                    ->options(EmploymentType::class)
                    ->multiple()
                    ->required(),
                Toggle::make('is_active')
                    ->label('Активна')
                    ->required(),
                DateTimePicker::make('published_at')
                    ->label('Дата публікації'),
            ]);
    }
}
