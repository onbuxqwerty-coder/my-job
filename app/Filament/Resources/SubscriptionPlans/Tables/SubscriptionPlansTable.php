<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubscriptionPlans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price_monthly')
                    ->label('Ціна / міс.')
                    ->money('UAH', locale: 'uk')
                    ->sortable(),
                TextColumn::make('features.active_jobs')
                    ->label('Вакансій')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '∞' : $state),
                TextColumn::make('features.hot_per_month')
                    ->label('HOT / міс.')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '—' : $state),
                TextColumn::make('features.top_per_month')
                    ->label('TOP / міс.')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '—' : $state),
                ToggleColumn::make('is_active')
                    ->label('Активний'),
            ])
            ->defaultSort('price_monthly')
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
