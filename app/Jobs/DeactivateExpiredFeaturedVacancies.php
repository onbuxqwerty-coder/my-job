<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Vacancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class DeactivateExpiredFeaturedVacancies implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $count = Vacancy::where('is_featured', true)
            ->where('featured_until', '<', now())
            ->update([
                'is_featured'    => false,
                'featured_until' => null,
            ]);

        logger()->info("Deactivated {$count} expired featured vacancies.");
    }
}
