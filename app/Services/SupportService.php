<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContactRole;
use App\Enums\SupportThreadStatus;
use App\Enums\UserRole;
use App\Mail\SupportMessageNotification;
use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SupportService
{
    public function __construct(
        private readonly TelegramNotifier $telegram,
    ) {}

    public function createThread(
        User $user,
        string $subject,
        string $body,
        ContactRole $role,
    ): SupportThread {
        $thread = SupportThread::create([
            'user_id'         => $user->id,
            'subject'         => $subject,
            'role'            => $role,
            'status'          => SupportThreadStatus::Open,
            'last_message_at' => now(),
        ]);

        SupportMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'body'      => $body,
            'is_read'   => false,
        ]);

        $this->notifyAdmin($thread);

        return $thread;
    }

    public function reply(
        SupportThread $thread,
        User $sender,
        string $body,
    ): SupportMessage {
        $message = SupportMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $sender->id,
            'body'      => $body,
            'is_read'   => false,
        ]);

        $thread->update(['last_message_at' => now()]);

        if ($this->isAdmin($sender)) {
            $this->notifyUser($thread, $message);
        } else {
            $this->notifyAdmin($thread);
        }

        return $message;
    }

    public function markThreadRead(SupportThread $thread, User $reader): void
    {
        $thread->messages()
            ->where('sender_id', '!=', $reader->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    // -------------------------------------------------------------------------

    private function notifyUser(SupportThread $thread, SupportMessage $message): void
    {
        $user = $thread->user;
        if (! $user) {
            return;
        }

        $threadUrl = url('/seeker/messages/' . $thread->id);

        if ($user->prefersEmail()) {
            Mail::to($user->email)
                ->send(new SupportMessageNotification($thread, $message, $user->name));

        } elseif ($user->prefersTelegram()) {
            $text = implode("\n", [
                "💬 Нова відповідь у зверненні «{$thread->subject}»",
                '',
                $message->body,
                '',
                "👉 {$threadUrl}",
            ]);
            $this->telegram->send((string) $user->telegram_id, $text);
        }
    }

    private function notifyAdmin(SupportThread $thread): void
    {
        $lastMessage = $thread->messages()->latest()->first();
        if (! $lastMessage) {
            return;
        }

        Mail::to(config('mail.support_address', 'support@myjob.co.ua'))
            ->send(new SupportMessageNotification($thread, $lastMessage, 'Команда підтримки'));
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
