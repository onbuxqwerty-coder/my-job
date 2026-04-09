<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label("Ім'я")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Електронна пошта')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('email_verified_at')
                    ->label('Пошта підтверджена')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\UserRole ? $state->label() : $state)
                    ->toggleable(),
                TextColumn::make('telegram_id')
                    ->label('Telegram ID')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—')
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
