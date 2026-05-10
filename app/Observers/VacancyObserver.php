<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RecalculateRecommendationsJob;
use App\Models\Vacancy;

class VacancyObserver
{
    public function saved(Vacancy $vacancy): void
    {
        if (! $vacancy->is_active) {
            return;
        }

        RecalculateRecommendationsJob::dispatch($vacancy);
    }
}
