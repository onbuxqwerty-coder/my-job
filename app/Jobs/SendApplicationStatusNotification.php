<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Application;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendApplicationStatusNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $applicationId,
    ) {}

    public function handle(TelegramService $service): void
    {
        $application = Application::with(['vacancy.company', 'user'])->find($this->applicationId);

        if (!$application || !$application->user->telegram_id) {
            return;
        }

        $vacancy = $application->vacancy;
        $status  = $application->status->label();

        $text = "📋 <b>Application Status Update</b>\n\n"
            . "Position: <b>{$vacancy->title}</b>\n"
            . "Company: <b>{$vacancy->company->name}</b>\n"
            . "New Status: <b>{$status}</b>\n\n"
            . "<a href=\"" . url("/jobs/{$vacancy->slug}") . "\">View Vacancy</a>";

        $service->sendMessage((int) $application->user->telegram_id, $text);
    }
}
