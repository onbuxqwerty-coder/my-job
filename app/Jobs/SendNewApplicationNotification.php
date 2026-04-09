<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Application;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendNewApplicationNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $applicationId,
    ) {}

    public function handle(TelegramService $service): void
    {
        $application = Application::with(['vacancy.company.user', 'user'])
            ->find($this->applicationId);

        if (!$application) {
            return;
        }

        $service->notifyEmployer($application);
    }
}
