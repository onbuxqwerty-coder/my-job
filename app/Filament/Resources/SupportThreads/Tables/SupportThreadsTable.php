<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportThreads\Tables;

use App\Enums\ContactRole;
use App\Enums\SupportThreadStatus;
use App\Models\SupportThread;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SupportThreadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Користувач')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('subject')
                    ->label('Тема')
                    ->searchable()
                    ->limit(40),

                BadgeColumn::make('role')
                    ->label('Роль')
                    ->formatStateUsing(fn (ContactRole $state) => $state->label())
                    ->colors([
                        'info'    => ContactRole::Seeker->value,
                        'warning' => ContactRole::Employer->value,
                        'success' => ContactRole::Partnership->value,
                        'gray'    => ContactRole::Other->value,
                    ]),

                BadgeColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (SupportThreadStatus $state) => $state->label())
                    ->colors([
                        'success' => SupportThreadStatus::Open->value,
                        'gray'    => SupportThreadStatus::Closed->value,
                    ]),

                TextColumn::make('messages_count')
                    ->label('Повідомлень')
                    ->counts('messages')
                    ->sortable(),

                TextColumn::make('unread_count')
                    ->label('Нових')
                    ->state(fn (SupportThread $record) =>
                        $record->messages()->where('is_read', false)->count()
                    )
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),

                TextColumn::make('last_message_at')
                    ->label('Остання активність')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(
                        collect(SupportThreadStatus::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                            ->toArray()
                    ),

                SelectFilter::make('role')
                    ->label('Роль')
                    ->options(
                        collect(ContactRole::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                            ->toArray()
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Видалити'),
                ]),
            ]);
    }
}
