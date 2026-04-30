<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ApplicationStatusChanged;
use App\Notifications\ApplicationStatusChangedNotification;
use Illuminate\Support\Facades\Notification;

class SendStatusNotification
{
    public function handle(ApplicationStatusChanged $event): void
    {
        $seeker   = $event->application->user;
        $employer = $event->application->vacancy->company->user;

        Notification::send(
            [$seeker, $employer],
            new ApplicationStatusChangedNotification($event),
        );
    }
}
