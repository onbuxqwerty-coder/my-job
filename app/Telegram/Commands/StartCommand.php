<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final class StartCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $text = $bot->message()?->text ?? '/start';
        $parts = explode(' ', $text, 2);
        $payload = trim($parts[1] ?? '');

        \Illuminate\Support\Facades\Log::info('StartCommand invoked', ['text' => $text, 'payload' => $payload]);

        if (str_starts_with($payload, 'job_')) {
            $this->handleJobDeepLink($bot, (int) substr($payload, 4));
            return;
        }

        if (str_starts_with($payload, 'link_')) {
            $this->handleAccountLink($bot, substr($payload, 5));
            return;
        }

        if (str_starts_with($payload, 'auth_')) {
            $this->handleAuthDeepLink($bot, substr($payload, 5));
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

    private function handleAuthDeepLink(Nutgram $bot, string $token): void
    {
        $telegramId = $bot->userId();

        \Illuminate\Support\Facades\Log::info('handleAuthDeepLink', [
            'telegramId' => $telegramId,
            'token'      => substr($token, 0, 8),
        ]);

        if (! $telegramId || ! $token) {
            $bot->sendMessage('❌ Недійсне посилання. Спробуйте ще раз на сайті.');
            return;
        }

        // Зберігаємо токен для подальшої обробки контакту
        Cache::put("tg_auth_pending:{$telegramId}", $token, 300);

        $keyboard = ReplyKeyboardMarkup::make(one_time_keyboard: true, resize_keyboard: true)
            ->addRow(KeyboardButton::make('📱 Поділитися контактом', request_contact: true));

        $bot->sendMessage(
            text: "👋 <b>Вхід на My Job</b>\n\nНатисніть кнопку нижче, щоб підтвердити свій номер телефону та авторизуватися на сайті.",
            parse_mode: 'HTML',
            reply_markup: $keyboard,
        );

        \Illuminate\Support\Facades\Log::info('handleAuthDeepLink: message sent', ['telegramId' => $telegramId]);
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
