<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessMonobankPayment;
use App\Services\MonobankService;
use Illuminate\Console\Command;

class CheckMonobankPayments extends Command
{
    protected $signature   = 'mono:check-payments';
    protected $description = 'Перевірити виписку Monobank і активувати pending-замовлення';

    public function handle(MonobankService $mono): void
    {
        $from = now()->subMinutes(35)->timestamp;
        $to   = now()->timestamp;

        $statements = $mono->getStatements($from, $to);

        foreach ($statements as $statement) {
            ProcessMonobankPayment::dispatch($statement);
        }

        $this->info('Перевірено ' . count($statements) . ' транзакцій.');
    }
}
