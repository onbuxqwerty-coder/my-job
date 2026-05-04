<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:deactivate-expired-promos')]
#[Description('Деактивує прострочені HOT/TOP промо вакансій')]
class DeactivateExpiredPromos extends Command
{
    public function handle(SubscriptionService $subscriptionService): int
    {
        $count = $subscriptionService->deactivateExpiredPromos();
        $this->info("Деактивовано {$count} прострочених промо.");

        return self::SUCCESS;
    }
}
