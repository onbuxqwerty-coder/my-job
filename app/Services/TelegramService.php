<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final class TelegramService
{
    public function __construct(
        private readonly Nutgram $bot,
    ) {}

    /**
     * Send an HTML message to a Telegram chat.
     */
    public function sendMessage(int $chatId, string $text, ?InlineKeyboardMarkup $keyboard = null): void
    {
        try {
            $this->bot->sendMessage(
                text: $text,
                chat_id: $chatId,
                parse_mode: 'HTML',
                reply_markup: $keyboard,
            );
        } catch (\Throwable $e) {
            logger()->error('Telegram sendMessage failed', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify the employer about a new application.
     */
    public function notifyEmployer(Application $application): void
    {
        $application->loadMissing(['vacancy.company.user', 'user']);

        $employer = $application->vacancy->company->user;

        if (!$employer->telegram_id) {
            return;
        }

        $vacancy = $application->vacancy;
        $vacancyUrl = url("/jobs/{$vacancy->slug}");

        $text = "🔔 <b>New application!</b>\n\n"
            . "📌 Vacancy: <b>{$vacancy->title}</b>\n"
            . "👤 Candidate: <b>{$application->user->name}</b>\n"
            . "📎 Resume: {$application->resume_url}\n\n"
            . "<a href=\"{$vacancyUrl}\">View vacancy on website</a>";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make('🔗 View Vacancy', url: $vacancyUrl)
        );

        $this->sendMessage((int) $employer->telegram_id, $text, $keyboard);
    }

    /**
     * Generate a unique link token for Telegram account linking.
     */
    public function generateLinkToken(User $user): string
    {
        $token = Str::random(32);
        $user->update(['telegram_link_token' => $token]);

        return $token;
    }
}
