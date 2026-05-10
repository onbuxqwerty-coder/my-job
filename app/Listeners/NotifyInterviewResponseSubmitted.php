<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InterviewResponseSubmitted;

class NotifyInterviewResponseSubmitted
{
    public function handle(InterviewResponseSubmitted $event): void
    {
        // TODO: відправка email роботодавцю про отриману відповідь
        // TODO: відправка Telegram-сповіщення через TelegramService
    }
}
