<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

final class DeactivateIncompleteProfileVacancies extends Command
{
    protected $signature   = 'app:deactivate-incomplete-profile-vacancies';
    protected $description = 'Deactivate vacancies published 24h+ ago by employers with incomplete company profile';

    public function handle(): int
    {
        $vacancies = Vacancy::where('status', VacancyStatus::Active)
            ->where('published_at', '<', now()->subDay())
            ->whereHas('company', function (Builder $q): void {
                $q->where(function (Builder $inner): void {
                    $inner->whereNull('description')
                          ->orWhere('description', '');
                })->orWhere('name', 'Компанія');
            })
            ->get();

        $count = 0;

        foreach ($vacancies as $vacancy) {
            $vacancy->forceFill([
                'status'    => VacancyStatus::Draft,
                'is_active' => false,
            ])->save();

            $count++;
        }

        $this->info("Deactivated {$count} vacancy/vacancies (incomplete employer profile).");

        return self::SUCCESS;
    }
}
