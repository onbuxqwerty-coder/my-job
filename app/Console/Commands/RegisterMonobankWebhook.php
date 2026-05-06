<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MonobankService;
use Illuminate\Console\Command;

class RegisterMonobankWebhook extends Command
{
    protected $signature   = 'mono:register-webhook';
    protected $description = 'Зареєструвати Webhook URL у Monobank';

    public function handle(): void
    {
        $this->warn('Корпоративний Monobank API не підтримує реєстрацію webhook через API.');
        $this->info('Для отримання webhook зверніться до підтримки Monobank або використовуйте cron-polling (mono:check-payments).');
    }
}
