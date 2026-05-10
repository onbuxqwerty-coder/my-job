<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployerSubscriptions\Tables;

use App\Enums\PlanType;
use App\Models\EmployerSubscription;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployerSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Роботодавець')
                    ->description(fn (EmployerSubscription $record) => $record->user?->email ?? '—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plan.name')
                    ->label('План')
                    ->description(fn (EmployerSubscription $record) => $record->plan?->type?->value
                        ? ucfirst($record->plan->type->value)
                        : '—')
                    ->sortable(),

                TextColumn::make('status_label')
                    ->label('Статус')
                    ->badge()
                    ->state(fn (EmployerSubscription $record) => $record->status === 'active' && $record->ends_at?->isAfter(now())
                        ? 'Активна'
                        : 'Неактивна')
                    ->color(fn (EmployerSubscription $record) => $record->status === 'active' && $record->ends_at?->isAfter(now())
                        ? 'success'
                        : 'gray'),

                TextColumn::make('starts_at')
                    ->label('Початок')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Закінчується')
                    ->date('d.m.Y')
                    ->color(fn (EmployerSubscription $record) => $record->ends_at?->isPast() ? 'danger' : null)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_computed')
                    ->label('Статус')
                    ->options([
                        'active'   => 'Активні',
                        'inactive' => 'Неактивні',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active'   => $query->where('status', 'active')->where('ends_at', '>', now()),
                            'inactive' => $query->where(fn ($q) => $q->where('status', '!=', 'active')->orWhere('ends_at', '<=', now())),
                            default    => $query,
                        };
                    }),

                SelectFilter::make('plan_type')
                    ->label('Тип плану')
                    ->options(
                        collect(PlanType::cases())
                            ->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)])
                            ->toArray()
                    )
                    ->query(fn (Builder $query, array $data): Builder =>
                        $data['value']
                            ? $query->whereHas('plan', fn ($q) => $q->where('type', $data['value']))
                            : $query
                    ),
            ])
            ->recordActions([
                ViewAction::make()->label('Переглянути'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
