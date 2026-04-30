<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationStatusChanged;
use App\Exceptions\UnauthorizedStatusChangeException;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ApplicationStatusService
{
    /**
     * Change application status with authorization check and history recording.
     *
     * @throws UnauthorizedStatusChangeException
     */
    public function changeStatus(
        Application $application,
        ApplicationStatus $newStatus,
        User $actor,
        string $actorRole,
        ?string $comment = null,
    ): Application {
        if (! in_array($actorRole, $newStatus->allowedActors(), true)) {
            throw new UnauthorizedStatusChangeException();
        }

        $oldStatus = $application->status;

        DB::transaction(function () use ($application, $newStatus, $oldStatus, $actor, $actorRole, $comment): void {
            $application->update(['status' => $newStatus]);

            ApplicationStatusHistory::create([
                'application_id' => $application->id,
                'from_status'    => $oldStatus?->value,
                'to_status'      => $newStatus->value,
                'changed_by'     => $actor->id,
                'actor_role'     => $actorRole,
                'comment'        => $comment,
            ]);

            event(new ApplicationStatusChanged($application, $oldStatus, $newStatus, $actor));
        });

        return $application->refresh();
    }
}
