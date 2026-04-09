<?php

declare(strict_types=1);

namespace App\Telegram\Callbacks;

use App\Models\Category;
use App\Models\TelegramSubscription;
use SergiX44\Nutgram\Nutgram;

final class AlertToggleCallback
{
    public function __invoke(Nutgram $bot): void
    {
        $data       = $bot->callbackQuery()?->data ?? '';
        $categoryId = (int) str_replace('alert_toggle:', '', $data);
        $telegramId = $bot->userId();

        if (!$categoryId) {
            $bot->answerCallbackQuery(text: 'Invalid selection.');
            return;
        }

        $category = Category::find($categoryId);

        if (!$category) {
            $bot->answerCallbackQuery(text: 'Category not found.');
            return;
        }

        $existing = TelegramSubscription::where('telegram_id', $telegramId)
            ->where('category_id', $categoryId)
            ->first();

        if ($existing) {
            $existing->delete();
            $bot->answerCallbackQuery(text: "❌ Unsubscribed from {$category->name}.");
        } else {
            TelegramSubscription::create([
                'telegram_id' => $telegramId,
                'category_id' => $categoryId,
            ]);
            $bot->answerCallbackQuery(text: "✅ Subscribed to {$category->name}!");
        }

        // Refresh the menu
        app(AlertsCommand::class)->__invoke($bot);
    }
}
