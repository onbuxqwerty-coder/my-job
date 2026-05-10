<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Models\Vacancy;
use App\Services\RecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RecalculateRecommendationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly User|Vacancy $target,
    ) {
        $this->onQueue('recommendations');
    }

    public function handle(RecommendationService $service): void
    {
        if ($this->target instanceof User) {
            $service->recalculateForUser($this->target);
        } elseif ($this->target instanceof Vacancy) {
            $service->recalculateForVacancy($this->target);
        }
    }
}
