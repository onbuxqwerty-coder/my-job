<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Models\User;
use App\Models\Vacancy;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final class StartCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $text = $bot->message()?->text ?? '/start';
        $parts = explode(' ', $text, 2);
        $payload = trim($parts[1] ?? '');

        if (str_starts_with($payload, 'job_')) {
            $this->handleJobDeepLink($bot, (int) substr($payload, 4));
            return;
        }

        if (str_starts_with($payload, 'link_')) {
            $this->handleAccountLink($bot, substr($payload, 5));
            return;
        }

        $bot->sendMessage(
            text: "👋 <b>Welcome to Job Board Bot!</b>\n\nUse this bot to receive instant notifications about new applications.",
            parse_mode: 'HTML',
        );
    }

    private function handleJobDeepLink(Nutgram $bot, int $vacancyId): void
    {
        /** @var Vacancy|null $vacancy */
        $vacancy = Vacancy::with('company')->find($vacancyId);

        if (!$vacancy) {
            $bot->sendMessage('❌ Vacancy not found or no longer available.');
            return;
        }

        $salary = $vacancy->salary_from
            ? number_format((int) $vacancy->salary_from) . ' – ' . number_format((int) $vacancy->salary_to) . " {$vacancy->currency}"
            : 'Not specified';

        $text = "🏢 <b>{$vacancy->title}</b>\n"
            . "🏭 Company: {$vacancy->company->name}\n"
            . "📍 Location: {$vacancy->company->location}\n"
            . "💼 Type: " . ucwords(str_replace('-', ' ', $vacancy->employment_type->value)) . "\n"
            . "💰 Salary: {$salary}";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make('🔗 View on Website', url: url("/jobs/{$vacancy->slug}"))
        );

        $bot->sendMessage(text: $text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    private function handleAccountLink(Nutgram $bot, string $token): void
    {
        /** @var User|null $user */
        $user = User::where('telegram_link_token', $token)->first();

        if (!$user) {
            $bot->sendMessage('❌ Invalid or expired link token. Please try again from your profile page.');
            return;
        }

        $user->update([
            'telegram_id'          => $bot->userId(),
            'telegram_link_token'  => null,
        ]);

        $bot->sendMessage('✅ Your Telegram account has been linked! You will now receive notifications.');
    }
}
