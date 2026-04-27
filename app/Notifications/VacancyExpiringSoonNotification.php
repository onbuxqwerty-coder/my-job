<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Vacancy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VacancyExpiringSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Vacancy $vacancy,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(object $notifiable): array
    {
        $title     = $this->vacancy->title ?? "#{$this->vacancy->id}";
        $expiresAt = $this->vacancy->expires_at->locale('uk')->isoFormat('D MMMM, HH:mm');
        $id        = $this->vacancy->id;

        $text = "⏰ <b>Вакансія завершиться завтра</b>\n\n"
              . "«{$title}»\n"
              . "↳ Завершення: <b>{$expiresAt}</b>\n\n"
              . "Хочете продовжити публікацію?";

        return [
            'chat_id'      => $notifiable->telegram_id,
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '15 днів — 100 ₴', 'callback_data' => "vac:ext:{$id}:15"],
                        ['text' => '30 днів — 200 ₴', 'callback_data' => "vac:ext:{$id}:30"],
                    ],
                    [
                        ['text' => '90 днів — 500 ₴', 'callback_data' => "vac:ext:{$id}:90"],
                    ],
                    [
                        ['text' => '📦 Архівувати',      'callback_data' => "vac:arc:{$id}"],
                        ['text' => '🔕 Не нагадувати',   'callback_data' => "vac:mut:{$id}"],
                    ],
                ],
            ]),
        ];
    }
}
