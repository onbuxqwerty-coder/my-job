<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Vacancy;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpiringSoonWidget extends BaseWidget
{
    protected static ?int $sort            = 2;
    protected ?string     $pollingInterval = '300s';

    public static function canView(): bool
    {
        return Vacancy::expiringSoon(72)->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Вакансії що завершуються (< 72 год)')
            ->query(
                Vacancy::expiringSoon(72)
                    ->with('company')
                    ->orderBy('expires_at')
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Вакансія')
                    ->limit(35)
                    ->url(fn (Vacancy $record): string =>
                        route('filament.admin.resources.vacancies.edit', $record)
                    ),

                TextColumn::make('company.name')
                    ->label('Компанія')
                    ->limit(25),

                TextColumn::make('expires_at')
                    ->label('Завершується')
                    ->dateTime('d.m.Y H:i')
                    ->color(fn (Vacancy $record): string =>
                        $record->hours_left !== null && $record->hours_left < 24
                            ? 'danger'
                            : 'warning'
                    ),

                TextColumn::make('countdown_label')
                    ->label('Залишок')
                    ->badge()
                    ->color(fn (Vacancy $record): string =>
                        $record->hours_left !== null && $record->hours_left < 24
                            ? 'danger'
                            : 'warning'
                    ),
            ])
            ->recordActions([
                Action::make('extend_30')
                    ->label('+30 днів')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (Vacancy $record): void {
                        $record->extend(30);
                        Notification::make()
                            ->success()
                            ->title('Вакансію продовжено на 30 днів')
                            ->send();
                    }),

                Action::make('archive')
                    ->label('Архів')
                    ->icon('heroicon-o-archive-box')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Vacancy $record): void {
                        $record->archive();
                        Notification::make()
                            ->success()
                            ->title('Вакансію архівовано')
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Немає вакансій що завершуються')
            ->paginated(false);
    }
}
