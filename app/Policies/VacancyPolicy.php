<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\VacancyStatus;
use App\Models\User;
use App\Models\Vacancy;

class VacancyPolicy
{
    public function extend(User $user, Vacancy $vacancy): bool
    {
        return $vacancy->company?->user_id === $user->id
            && $vacancy->status !== VacancyStatus::Archived;
    }

    public function view(User $user, Vacancy $vacancy): bool
    {
        return $vacancy->company?->user_id === $user->id;
    }
}
