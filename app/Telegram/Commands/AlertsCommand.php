<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Models\Category;
use App\Models\TelegramSubscription;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final class AlertsCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $telegramId     = $bot->userId();
        $subscriptions  = TelegramSubscription::where('telegram_id', $telegramId)
            ->pluck('category_id')
            ->toArray();

        $categories = Category::orderBy('position')->orderBy('name')->get();

        $keyboard = InlineKeyboardMarkup::make();

        foreach ($categories as $category) {
            $isSubscribed = in_array($category->id, $subscriptions, true);
            $label        = ($isSubscribed ? '✅ ' : '') . $category->name;

            $keyboard->addRow(
                InlineKeyboardButton::make(
                    $label,
                    callback_data: "alert_toggle:{$category->id}"
                )
            );
        }

        $bot->sendMessage(
            text: "🔔 <b>Job Alerts</b>\n\nSelect categories to receive notifications about new vacancies:",
            parse_mode: 'HTML',
            reply_markup: $keyboard,
        );
    }
}
