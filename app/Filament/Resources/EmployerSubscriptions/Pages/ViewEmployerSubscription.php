<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployerSubscriptions\Pages;

use App\Filament\Resources\EmployerSubscriptions\EmployerSubscriptionResource;
use App\Models\EmployerSubscription;
use App\Services\SubscriptionService;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewEmployerSubscription extends ViewRecord
{
    protected static string $resource = EmployerSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Скасувати підписку')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'active' && $this->record->ends_at?->isAfter(now()))
                ->action(function (): void {
                    /** @var EmployerSubscription $subscription */
                    $subscription = $this->record;
                    app(SubscriptionService::class)->cancel($subscription);
                    $this->refreshFormData(['status', 'cancelled_at']);

                    Notification::make()
                        ->success()
                        ->title('Підписку скасовано')
                        ->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Підписка')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Роботодавець'),

                    TextEntry::make('user.email')
                        ->label('Email'),

                    TextEntry::make('plan.name')
                        ->label('Тариф'),

                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),

                    TextEntry::make('starts_at')
                        ->label('Початок')
                        ->dateTime('d.m.Y'),

                    TextEntry::make('ends_at')
                        ->label('Закінчується')
                        ->dateTime('d.m.Y')
                        ->color(fn ($record) => $record->ends_at?->isPast() ? 'danger' : null),

                    TextEntry::make('cancelled_at')
                        ->label('Скасовано')
                        ->dateTime('d.m.Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('payment_reference')
                        ->label('Платіжна референція')
                        ->placeholder('—')
                        ->copyable(),
                ]),

            Section::make('Використання')
                ->columns(3)
                ->schema([
                    TextEntry::make('active_jobs_count')
                        ->label('Активних вакансій')
                        ->state(fn (EmployerSubscription $record): int =>
                            app(SubscriptionService::class)->activeJobsCount($record->user)
                        ),

                    TextEntry::make('remaining_hot')
                        ->label('Залишок HOT')
                        ->state(fn (EmployerSubscription $record): int =>
                            app(SubscriptionService::class)->getRemainingHot($record->user)
                        ),

                    TextEntry::make('can_publish')
                        ->label('Може публікувати')
                        ->state(fn (EmployerSubscription $record): string =>
                            app(SubscriptionService::class)->canPublishJob($record->user) ? 'Так' : 'Ні'
                        )
                        ->color(fn (EmployerSubscription $record): string =>
                            app(SubscriptionService::class)->canPublishJob($record->user) ? 'success' : 'danger'
                        ),
                ]),
        ]);
    }
}
