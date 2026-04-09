<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Vacancy;
use Illuminate\Console\Command;

final class DeactivateExpiredFeatured extends Command
{
    protected $signature   = 'app:deactivate-expired-featured';
    protected $description = 'Deactivate vacancies whose featured period has expired';

    public function handle(): int
    {
        $count = Vacancy::where('is_featured', true)
            ->where('featured_until', '<', now())
            ->update([
                'is_featured'    => false,
                'featured_until' => null,
            ]);

        $this->info("Deactivated {$count} expired featured vacancy/vacancies.");

        return self::SUCCESS;
    }
}
