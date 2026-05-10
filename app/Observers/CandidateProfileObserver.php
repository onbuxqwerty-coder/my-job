<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\UserRole;
use App\Jobs\RecalculateRecommendationsJob;
use App\Models\User;

class CandidateProfileObserver
{
    public function saved(User $user): void
    {
        if ($user->role !== UserRole::Candidate) {
            return;
        }

        RecalculateRecommendationsJob::dispatch($user);
    }
}
