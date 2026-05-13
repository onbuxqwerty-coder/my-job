<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportThreads\Pages;

use App\Enums\SupportThreadStatus;
use App\Enums\UserRole;
use App\Filament\Resources\SupportThreads\SupportThreadResource;
use App\Models\SupportThread;
use App\Services\SupportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewSupportThread extends ViewRecord
{
    protected static string $resource = SupportThreadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Відповісти')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $this->record->status === SupportThreadStatus::Open)
                ->form([
                    Textarea::make('body')
                        ->label('Повідомлення')
                        ->required()
                        ->minLength(2)
                        ->rows(5)
                        ->placeholder('Введіть відповідь...'),
                ])
                ->action(function (array $data, SupportService $service): void {
                    $admin = auth()->user();
                    $service->reply(
                        thread: $this->record,
                        sender: $admin,
                        body: $data['body'],
                    );

                    Notification::make()
                        ->title('Відповідь надіслано')
                        ->success()
                        ->send();

                    $this->refreshFormData([]);
                }),

            Action::make('close')
                ->label('Закрити звернення')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Закрити звернення?')
                ->modalDescription('Статус зміниться на «Закрито». Відповісти можна буде після відкриття.')
                ->visible(fn () => $this->record->status === SupportThreadStatus::Open)
                ->action(function (): void {
                    $this->record->update(['status' => SupportThreadStatus::Closed]);
                    Notification::make()->title('Звернення закрито')->send();
                }),

            Action::make('reopen')
                ->label('Відкрити знову')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => $this->record->status === SupportThreadStatus::Closed)
                ->action(function (): void {
                    $this->record->update(['status' => SupportThreadStatus::Open]);
                    Notification::make()->title('Звернення відкрито')->success()->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        /** @var SupportThread $thread */
        $thread = $this->record->load('user', 'messages.sender');

        return $schema->components([
            Section::make('Деталі звернення')
                ->columns(3)
                ->schema([
                    TextEntry::make('subject')
                        ->label('Тема'),

                    TextEntry::make('user.name')
                        ->label('Користувач')
                        ->state(fn () => $thread->user?->name . ' (' . $thread->user?->email . ')'),

                    TextEntry::make('role')
                        ->label('Роль')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state->label()),

                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state->label())
                        ->color(fn ($state) => $state === SupportThreadStatus::Open ? 'success' : 'gray'),

                    TextEntry::make('created_at')
                        ->label('Створено')
                        ->dateTime('d.m.Y H:i'),

                    TextEntry::make('last_message_at')
                        ->label('Остання активність')
                        ->dateTime('d.m.Y H:i'),
                ]),

            Section::make('Переписка')
                ->schema([
                    TextEntry::make('conversation')
                        ->label('')
                        ->html()
                        ->state(function () use ($thread): string {
                            return $thread->messages->map(function ($msg) {
                                $isAdmin = $msg->sender?->role === UserRole::Admin;
                                $bg      = $isAdmin ? '#EFF6FF' : '#F9FAFB';
                                $align   = $isAdmin ? 'right' : 'left';
                                $name    = e($msg->sender?->name ?? 'Невідомо');
                                $date    = $msg->created_at->format('d.m.Y H:i');
                                $body    = nl2br(e($msg->body));

                                return <<<HTML
                                    <div style="margin-bottom:16px; text-align:{$align};">
                                        <div style="display:inline-block; max-width:80%; background:{$bg};
                                                    border:1px solid #E5E7EB; border-radius:12px;
                                                    padding:12px 16px; text-align:left;">
                                            <div style="font-size:11px; color:#6B7280; margin-bottom:4px;">
                                                <strong>{$name}</strong> · {$date}
                                            </div>
                                            <div style="font-size:14px; color:#111827; line-height:1.5;">{$body}</div>
                                        </div>
                                    </div>
                                HTML;
                            })->implode('');
                        }),
                ]),
        ]);
    }
}
