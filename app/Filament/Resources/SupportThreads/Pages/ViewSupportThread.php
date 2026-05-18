<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportThreads\Pages;

use App\Enums\SupportThreadStatus;
use App\Enums\UserRole;
use App\Filament\Resources\SupportThreads\SupportThreadResource;
use App\Models\SupportThread;
use App\Services\SupportService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;

class ViewSupportThread extends ViewRecord
{
    protected static string $resource = SupportThreadResource::class;

    public string $replyBody = '';

    public function sendReply(SupportService $service): void
    {
        $this->validate(['replyBody' => ['required', 'string', 'min:1', 'max:5000']]);

        if ($this->record->status !== SupportThreadStatus::Open) {
            Notification::make()->title('Звернення закрито')->warning()->send();
            return;
        }

        $service->reply($this->record, auth()->user(), $this->replyBody);

        $this->replyBody = '';
        $this->record    = $this->record->fresh('messages.sender');

        Notification::make()->title('Відповідь надіслано')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close')
                ->label('Закрити звернення')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Закрити звернення?')
                ->modalDescription('Статус зміниться на «Закрито».')
                ->visible(fn () => $this->record->status === SupportThreadStatus::Open)
                ->action(function (): void {
                    $this->record->update(['status' => SupportThreadStatus::Closed]);
                    $this->record = $this->record->fresh();
                    Notification::make()->title('Звернення закрито')->send();
                }),

            Action::make('reopen')
                ->label('Відкрити знову')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => $this->record->status === SupportThreadStatus::Closed)
                ->action(function (): void {
                    $this->record->update(['status' => SupportThreadStatus::Open]);
                    $this->record = $this->record->fresh();
                    Notification::make()->title('Звернення відкрито')->success()->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        /** @var SupportThread $thread */
        $thread = $this->record->load('user', 'messages.sender');

        return $schema->components([
            Section::make('Деталі')
                ->columns(3)
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Користувач')
                        ->state(fn () => ($thread->user?->name ?? '—') . ' (' . ($thread->user?->email ?? '') . ')'),

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
                            if ($thread->messages->isEmpty()) {
                                return '<p style="color:#9ca3af;font-size:13px;">Повідомлень поки немає.</p>';
                            }

                            return $thread->messages->map(function ($msg) {
                                $isAdmin = $msg->sender?->role === UserRole::Admin;
                                $bg      = $isAdmin ? '#EFF6FF' : '#F9FAFB';
                                $align   = $isAdmin ? 'right' : 'left';
                                $name    = e($msg->sender?->name ?? 'Невідомо');
                                $date    = $msg->created_at->format('d.m.Y H:i');
                                $body    = nl2br(e($msg->body));

                                return <<<HTML
                                <div style="margin-bottom:12px;text-align:{$align};">
                                    <div style="display:inline-block;max-width:80%;background:{$bg};
                                                border:1px solid #E5E7EB;border-radius:12px;
                                                padding:10px 14px;text-align:left;">
                                        <div style="font-size:11px;color:#6B7280;margin-bottom:4px;">
                                            <strong>{$name}</strong> · {$date}
                                        </div>
                                        <div style="font-size:13px;color:#111827;line-height:1.5;">{$body}</div>
                                    </div>
                                </div>
                                HTML;
                            })->implode('');
                        }),
                ]),

            Section::make('Відповісти')
                ->visible(fn () => $this->record->status === SupportThreadStatus::Open)
                ->schema([
                    TextEntry::make('reply_form')
                        ->label('')
                        ->html()
                        ->state(fn () => <<<'HTML'
                            <div>
                                <textarea
                                    wire:model="replyBody"
                                    rows="4"
                                    placeholder="Написати відповідь..."
                                    style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px;
                                           font-size:13px;resize:vertical;outline:none;font-family:inherit;
                                           box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#3b82f6'"
                                    onblur="this.style.borderColor='#d1d5db'"
                                ></textarea>
                                <div style="display:flex;justify-content:flex-end;margin-top:8px;">
                                    <button
                                        wire:click="sendReply"
                                        wire:loading.attr="disabled"
                                        style="background:#2563eb;color:#fff;font-size:13px;font-weight:600;
                                               padding:8px 20px;border-radius:8px;border:none;cursor:pointer;"
                                    >
                                        Надіслати
                                    </button>
                                </div>
                            </div>
                        HTML),
                ]),
        ]);
    }
}
