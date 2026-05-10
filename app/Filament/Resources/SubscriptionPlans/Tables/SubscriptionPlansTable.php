<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubscriptionPlans\Tables;

use App\Enums\PlanType;
use App\Models\SubscriptionPlan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (PlanType $state) => ucfirst($state->value))
                    ->color(fn (PlanType $state) => match ($state) {
                        PlanType::Free     => 'gray',
                        PlanType::Start    => 'info',
                        PlanType::Business => 'warning',
                        PlanType::Pro      => 'success',
                    })
                    ->sortable(),

                TextColumn::make('price_monthly')
                    ->label('Ціна (грн/міс)')
                    ->suffix(' ₴')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('subscriptions_count')
                    ->label('Підписок')
                    ->counts('subscriptions')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активні')
                    ->placeholder('Всі')
                    ->trueLabel('Лише активні')
                    ->falseLabel('Лише неактивні'),
            ])
            ->recordActions([
                EditAction::make()->label('Редагувати'),

                DeleteAction::make()
                    ->label('Видалити')
                    ->requiresConfirmation()
                    ->before(function (DeleteAction $action, SubscriptionPlan $record): void {
                        if ($record->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Неможливо видалити')
                                ->body('На цьому плані є активні підписки.')
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Видалити'),
                ]),
            ])
            ->defaultSort('price_monthly');
    }
}
