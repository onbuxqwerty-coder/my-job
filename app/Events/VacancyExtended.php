<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Vacancy;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VacancyExtended
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Vacancy $vacancy,
        public readonly int $days,
        public readonly int $amountCents,
        public readonly string $currency,
        public readonly string $stripeEventId,
    ) {}
}
