<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\VacancyPublicationType;
use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Illuminate\Console\Command;

class RefreshAnonymousVacancies extends Command
{
    protected $signature   = 'vacancies:refresh-anonymous';
    protected $description = 'Щотижневе оновлення published_at для анонімних вакансій';

    public function handle(): void
    {
        $count = Vacancy::query()
            ->where('publication_type', VacancyPublicationType::Anonymous)
            ->where('status', VacancyStatus::Active)
            ->where('auto_refresh', true)
            ->where('auto_refresh_until', '>', now())
            ->update(['published_at' => now()]);

        $this->info("Оновлено вакансій: {$count}");
    }
}
