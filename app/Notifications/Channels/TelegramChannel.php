<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use SergiX44\Nutgram\Nutgram;

class TelegramChannel
{
    public function __construct(private readonly Nutgram $bot) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $payload = $notification->toTelegram($notifiable);

        $this->bot->sendMessage(
            text: $payload['text'],
            chat_id: $payload['chat_id'],
            parse_mode: $payload['parse_mode'] ?? null,
            reply_markup: $payload['reply_markup'] ?? null,
        );
    }
}
