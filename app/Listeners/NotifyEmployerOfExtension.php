<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\VacancyExtended;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class NotifyEmployerOfExtension implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly Nutgram $bot) {}

    public function handle(VacancyExtended $event): void
    {
        $user = $event->vacancy->company?->user;

        if (! $user || ! $user->telegram_id) {
            return;
        }

        $title      = $event->vacancy->title;
        $expiresAt  = $event->vacancy->expires_at?->locale('uk')->isoFormat('D MMMM, HH:mm');
        $amountUah  = number_format($event->amountCents / 100, 0, '.', ' ');

        $text = "✅ <b>Вакансію продовжено</b>\n\n"
              . "«{$title}» активна до <b>{$expiresAt}</b>.\n"
              . "Оплачено: {$amountUah} {$event->currency}.\n\n"
              . "Дякуємо!";

        try {
            $this->bot->sendMessage(
                chat_id: $user->telegram_id,
                text: $text,
                parse_mode: 'HTML',
            );
        } catch (\Throwable $e) {
            Log::channel('vacancies')->error('NotifyEmployerOfExtension: failed', [
                'vacancy_id' => $event->vacancy->id,
                'user_id'    => $user->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
