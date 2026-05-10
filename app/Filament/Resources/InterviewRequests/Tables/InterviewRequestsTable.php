<?php

declare(strict_types=1);

namespace App\Filament\Resources\InterviewRequests\Tables;

use App\Enums\InterviewRequestStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InterviewRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application.user.name')
                    ->label('Кандидат')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('application.vacancy.title')
                    ->label('Вакансія')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (InterviewRequestStatus $state) => $state->label())
                    ->color(fn (InterviewRequestStatus $state) => match ($state) {
                        InterviewRequestStatus::Pending  => 'warning',
                        InterviewRequestStatus::Answered => 'success',
                        InterviewRequestStatus::Expired  => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('deadline_at')
                    ->label('Дедлайн')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Надіслано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(
                        collect(InterviewRequestStatus::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                            ->toArray()
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null);
    }
}
