<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Власник')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('user.phone')
                    ->label('Телефон')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('logo')
                    ->label('Логотип')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('website')
                    ->label('Вебсайт')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('location')
                    ->label('Місто')
                    ->searchable()
                    ->toggleable(),
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
