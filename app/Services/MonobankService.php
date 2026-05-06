<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonobankService
{
    protected string $baseUrl = 'https://api.monobank.ua';
    protected string $token;
    protected string $accountId;

    public function __construct()
    {
        $this->token     = config('services.monobank.token');
        $this->accountId = config('services.monobank.account_id');
    }

    /**
     * Отримати виписку за діапазон часу. Monobank повертає суми в копійках.
     */
    public function getStatements(int $fromTimestamp, int $toTimestamp): array
    {
        $response = Http::withHeaders(['X-Token' => $this->token])
            ->get("{$this->baseUrl}/personal/statement/{$this->accountId}/{$fromTimestamp}/{$toTimestamp}");

        if ($response->failed()) {
            Log::error('Monobank statement error', ['body' => $response->body()]);
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Зареєструвати Webhook URL у Monobank (викликати один раз).
     */
    public function registerWebhook(string $url): bool
    {
        $response = Http::withHeaders(['X-Token' => $this->token])
            ->post("{$this->baseUrl}/personal/webhook", ['webHookUrl' => $url]);

        return $response->successful();
    }
}
