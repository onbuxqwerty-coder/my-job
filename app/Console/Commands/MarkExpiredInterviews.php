<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AsyncInterviewService;
use Illuminate\Console\Command;

final class MarkExpiredInterviews extends Command
{
    protected $signature   = 'interviews:mark-expired';
    protected $description = 'Mark pending async interview requests with past deadlines as expired';

    public function handle(AsyncInterviewService $service): int
    {
        $count = $service->markExpired();

        $this->info("Marked {$count} interview request(s) as expired.");

        return self::SUCCESS;
    }
}
