<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TelegramSubscription;
use App\Models\Vacancy;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final class SendVacancyAlerts extends Command
{
    protected $signature   = 'app:send-vacancy-alerts';
    protected $description = 'Send Telegram alerts for new vacancies to subscribed users';

    public function handle(TelegramService $telegram): int
    {
        $since = now()->subHour();

        $vacancies = Vacancy::with(['company', 'category'])
            ->where('is_active', true)
            ->where('published_at', '>=', $since)
            ->get();

        if ($vacancies->isEmpty()) {
            $this->info('No new vacancies in the last hour.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($vacancies as $vacancy) {
            $subscribers = TelegramSubscription::where('category_id', $vacancy->category_id)
                ->pluck('telegram_id');

            if ($subscribers->isEmpty()) {
                continue;
            }

            $salary = $vacancy->salary_from
                ? "\n💰 " . number_format((int) $vacancy->salary_from) . '–' . number_format((int) $vacancy->salary_to) . " {$vacancy->currency}"
                : '';

            $text = "🆕 <b>New Vacancy in {$vacancy->category->name}</b>\n\n"
                . "📌 <b>{$vacancy->title}</b>\n"
                . "🏭 {$vacancy->company->name} · {$vacancy->company->location}"
                . $salary;

            $keyboard = InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make('🔗 View Job', url: url("/jobs/{$vacancy->slug}"))
            );

            foreach ($subscribers as $telegramId) {
                $telegram->sendMessage((int) $telegramId, $text, $keyboard);
                $sent++;
            }
        }

        $this->info("Sent {$sent} alert(s) for {$vacancies->count()} new vacancies.");

        return self::SUCCESS;
    }
}
