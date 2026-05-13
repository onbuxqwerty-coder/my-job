<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function send(string $chatId, string $text): bool
    {
        $botApiUrl = config('services.telegram_bot.api_url');

        try {
            $response = Http::timeout(5)->post("{$botApiUrl}/send-message", [
                'chat_id' => $chatId,
                'text'    => $text,
            ]);

            if (! $response->successful()) {
                Log::warning('TelegramNotifier: failed', [
                    'chat_id' => $chatId,
                    'status'  => $response->status(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramNotifier: exception', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
