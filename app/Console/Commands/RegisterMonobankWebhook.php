<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MonobankService;
use Illuminate\Console\Command;

class RegisterMonobankWebhook extends Command
{
    protected $signature   = 'mono:register-webhook';
    protected $description = 'Зареєструвати Webhook URL у Monobank';

    public function handle(MonobankService $mono): void
    {
        $url = route('mono.webhook');

        if ($mono->registerWebhook($url)) {
            $this->info("Webhook зареєстровано: {$url}");
        } else {
            $this->error('Помилка реєстрації Webhook. Перевір MONO_TOKEN і доступність URL.');
        }
    }
}
