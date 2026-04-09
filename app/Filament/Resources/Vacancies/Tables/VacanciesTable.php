<?php

namespace App\Filament\Resources\Vacancies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VacanciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Компанія')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Категорія')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Назва')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('salary_from')
                    ->label('Зарплата від')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('salary_to')
                    ->label('Зарплата до')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Валюта')
                    ->searchable(),
                TextColumn::make('employment_type')
                    ->label('Тип зайнятості')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Опубліковано')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->label('Редагувати'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Видалити'),
                ]),
            ]);
    }
}
