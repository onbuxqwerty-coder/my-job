<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ApplicationStatusChanged;

class NotifyApplicationStatusChanged
{
    public function handle(ApplicationStatusChanged $event): void
    {
        // TODO: відправка email-сповіщення кандидату через Mail
        // TODO: відправка Telegram-сповіщення через TelegramService
    }
}
