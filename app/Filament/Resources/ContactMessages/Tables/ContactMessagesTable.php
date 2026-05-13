<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Enums\ContactRole;
use App\Models\ContactMessage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ім\'я')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact')
                    ->label('Контакт')
                    ->searchable(),

                BadgeColumn::make('role')
                    ->label('Роль')
                    ->formatStateUsing(fn (ContactRole $state) => $state->label())
                    ->colors([
                        'info'    => ContactRole::Seeker->value,
                        'warning' => ContactRole::Employer->value,
                        'success' => ContactRole::Partnership->value,
                        'gray'    => ContactRole::Other->value,
                    ]),

                TextColumn::make('topic')
                    ->label('Тема')
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('message')
                    ->label('Повідомлення')
                    ->limit(60),

                IconColumn::make('is_read')
                    ->label('Прочитано')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Роль')
                    ->options(
                        collect(ContactRole::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                            ->toArray()
                    ),
                TernaryFilter::make('is_read')
                    ->label('Прочитано'),
            ])
            ->recordActions([
                Action::make('markRead')
                    ->label('Позначити прочитаним')
                    ->icon('heroicon-o-check')
                    ->visible(fn (ContactMessage $record) => ! $record->is_read)
                    ->action(fn (ContactMessage $record) => $record->update(['is_read' => true])),

                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Видалити'),
                ]),
            ]);
    }
}
