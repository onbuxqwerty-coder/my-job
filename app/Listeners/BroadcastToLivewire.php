<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ApplicationStatusChanged;

class BroadcastToLivewire
{
    public function handle(ApplicationStatusChanged $event): void
    {
        // Real-time sync via WebSocket broadcasting (ShouldBroadcast on the event).
        // In-process dispatch for same-request Livewire component updates.
        try {
            \Livewire\Livewire::dispatch(
                'application-status-updated',
                applicationId: $event->application->id,
                newStatus: $event->newStatus->value,
            );
        } catch (\Throwable) {
            // Not in a Livewire request context — broadcast handles real-time sync.
        }
    }
}
