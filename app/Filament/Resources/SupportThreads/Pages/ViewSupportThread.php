<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportThreads\Pages;

use App\Enums\SupportThreadStatus;
use App\Filament\Resources\SupportThreads\SupportThreadResource;
use App\Models\SupportMessage;
use App\Services\SupportService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportThread extends ViewRecord
{
    protected static string $resource = SupportThreadResource::class;

    protected string $view = 'filament.resources.support-threads.pages.view-support-thread';

    public string  $replyBody        = '';
    public ?int    $editingMessageId = null;
    public string  $editBody         = '';

    // ── Reply ─────────────────────────────────────────────────────────────────

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

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function startEdit(int $messageId): void
    {
        $msg = SupportMessage::where('id', $messageId)
            ->where('thread_id', $this->record->id)
            ->firstOrFail();

        $this->editingMessageId = $messageId;
        $this->editBody         = $msg->body;
    }

    public function saveEdit(): void
    {
        $this->validate(['editBody' => ['required', 'string', 'min:1', 'max:5000']]);

        SupportMessage::where('id', $this->editingMessageId)
            ->where('thread_id', $this->record->id)
            ->update(['body' => $this->editBody]);

        $this->editingMessageId = null;
        $this->editBody         = '';
        $this->record           = $this->record->fresh('messages.sender');

        Notification::make()->title('Повідомлення оновлено')->success()->send();
    }

    public function cancelEdit(): void
    {
        $this->editingMessageId = null;
        $this->editBody         = '';
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function deleteMessage(int $messageId): void
    {
        SupportMessage::where('id', $messageId)
            ->where('thread_id', $this->record->id)
            ->delete();

        $this->record = $this->record->fresh('messages.sender');

        $lastMsg = $this->record->messages()->latest()->first();
        $this->record->update(['last_message_at' => $lastMsg?->created_at]);

        Notification::make()->title('Повідомлення видалено')->success()->send();
    }

    // ── Header actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close')
                ->label('Закрити')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Закрити звернення?')
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
}
