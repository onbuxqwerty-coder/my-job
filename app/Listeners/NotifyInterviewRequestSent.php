<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InterviewRequestSent;

class NotifyInterviewRequestSent
{
    public function handle(InterviewRequestSent $event): void
    {
        // TODO: відправка email кандидату з посиланням на інтерв'ю
        // TODO: відправка Telegram-сповіщення через TelegramService
    }
}
