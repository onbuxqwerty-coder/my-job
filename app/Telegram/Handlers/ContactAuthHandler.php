<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use App\Services\TelegramAuthService;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;

final class ContactAuthHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $contact    = $bot->message()?->contact;
        $telegramId = $bot->userId();

        if (! $contact || ! $telegramId) {
            return;
        }

        // Якщо контакт не свій — ігноруємо
        if ($contact->user_id && $contact->user_id !== $telegramId) {
            $bot->sendMessage('Будь ласка, поділіться власним контактом.');
            return;
        }

        $phone = $contact->phone_number;
        $token = Cache::get("tg_auth_pending:{$telegramId}");

        if (! $token) {
            // Немає активної сесії авторизації — ігноруємо
            return;
        }

        $service = app(TelegramAuthService::class);
        $success = $service->processContact($token, $telegramId, $phone);

        Cache::forget("tg_auth_pending:{$telegramId}");

        if ($success) {
            $bot->sendMessage(
                text: "✅ <b>Авторизацію підтверджено!</b>\n\nПовертайтесь на сайт — вхід відбудеться автоматично.",
                parse_mode: 'HTML',
            );
        } else {
            $bot->sendMessage('❌ Сесія прострочена або недійсна. Спробуйте ще раз на сайті.');
        }
    }
}
