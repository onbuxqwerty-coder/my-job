<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vacancy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class SeoService
{
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * @return array<string, string>
     */
    public function forHome(): array
    {
        return Cache::remember('seo:home', self::CACHE_TTL, function (): array {
            $title = config('app.name') . ' — Find Jobs in Ukraine';

            return [
                'title'          => $title,
                'description'    => 'Search thousands of job vacancies across Ukraine. Filter by category, employment type, and salary.',
                'og_title'       => $title,
                'og_description' => 'Find your next job in Ukraine. IT, Sales, Marketing, Healthcare and more.',
                'og_image'       => asset('images/og-default.jpg'),
                'canonical'      => url('/'),
            ];
        });
    }

    /**
     * @return array<string, string>
     */
    public function forVacancy(Vacancy $vacancy): array
    {
        return Cache::remember("seo:vacancy:{$vacancy->id}", self::CACHE_TTL, function () use ($vacancy): array {
            $title       = "{$vacancy->title} — {$vacancy->company->name} | " . config('app.name');
            $description = Str::limit(strip_tags($vacancy->description), 160);
            $salary      = $vacancy->salary_from
                ? "Salary: {$vacancy->salary_from}–{$vacancy->salary_to} {$vacancy->currency}. "
                : '';

            return [
                'title'          => $title,
                'description'    => $salary . $description,
                'og_title'       => "{$vacancy->title} at {$vacancy->company->name}",
                'og_description' => $description,
                'og_image'       => $vacancy->company->logo ?? asset('images/og-default.jpg'),
                'canonical'      => url("/jobs/{$vacancy->slug}"),
            ];
        });
    }
}
