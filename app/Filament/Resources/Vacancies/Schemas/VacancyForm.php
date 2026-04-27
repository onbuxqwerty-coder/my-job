<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
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
                Section::make('Основна інформація')
                    ->schema([
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
                            ->label('Активна (legacy)')
                            ->helperText('Використовуй статус нижче для управління публікацією.'),
                    ])
                    ->columns(2),

                Section::make('Життєвий цикл')
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options(VacancyStatus::options())
                            ->default(VacancyStatus::Draft->value)
                            ->required()
                            ->live()
                            ->helperText(fn ($state) =>
                                $state ? VacancyStatus::from($state)->description() : null
                            ),

                        DateTimePicker::make('published_at')
                            ->label('Дата публікації')
                            ->seconds(false)
                            ->locale('uk')
                            ->displayFormat('d.m.Y H:i')
                            ->helperText('Час, коли вакансію вперше опублікували.'),

                        DateTimePicker::make('expires_at')
                            ->label('Дата завершення')
                            ->seconds(false)
                            ->locale('uk')
                            ->displayFormat('d.m.Y H:i')
                            ->after('published_at')
                            ->helperText('Після цієї дати вакансія перейде в "Завершено".'),
                    ])
                    ->columns(2),
            ]);
    }
}
