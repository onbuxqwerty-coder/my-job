<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('city.name')
                    ->label('Місто')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('user.phone')
                    ->label('Телефон')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Контактна особа')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('website')
                    ->label('Вебсайт')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                ImageColumn::make('logo')
                    ->label('Логотип')
                    ->circular()
                    ->toggleable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Дата реєстрації')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
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
