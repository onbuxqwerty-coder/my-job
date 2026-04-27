<?php

declare(strict_types=1);

namespace App\Services\Vacancies;

use App\Models\Vacancy;
use Illuminate\Support\Collection;

class SimilarVacanciesService
{
    /**
     * Шукає схожі активні вакансії: та сама категорія + місто (fallback — лише категорія).
     */
    public function findFor(Vacancy $vacancy, int $limit = 6): Collection
    {
        $query = Vacancy::active()
            ->with(['company', 'category', 'city'])
            ->where('id', '!=', $vacancy->id);

        if ($vacancy->category_id) {
            $query->where('category_id', $vacancy->category_id);
        }

        if ($vacancy->city_id) {
            $query->orderByRaw('CASE WHEN city_id = ? THEN 0 ELSE 1 END', [$vacancy->city_id]);
        }

        return $query
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}
