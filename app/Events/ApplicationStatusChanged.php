<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Application $application,
        public readonly ?ApplicationStatus $oldStatus,
        public readonly ApplicationStatus $newStatus,
        public readonly User $changedBy,
    ) {}

    /** @return array<Channel> */
    public function broadcastOn(): array
    {
        $seekerId   = $this->application->user_id;
        $employerId = $this->application->vacancy->company->user_id;

        return [
            new PrivateChannel("seeker.{$seekerId}"),
            new PrivateChannel("employer.{$employerId}"),
        ];
    }
}
