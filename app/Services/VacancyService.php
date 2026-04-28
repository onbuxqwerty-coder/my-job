<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\VacancySearchDTO;
use App\Models\Vacancy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class VacancyService
{
    /**
     * Search and filter active vacancies.
     * Featured vacancies are always sorted first.
     */
    public function search(VacancySearchDTO $dto): LengthAwarePaginator
    {
        return Vacancy::query()
            ->with(['company', 'category', 'city'])
            ->active()
            ->when($dto->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($dto->categoryId, fn(Builder $q, int $id): Builder => $q->where('category_id', $id))
            ->when($dto->employmentTypes, function (Builder $q, array $types): void {
                $q->where(function (Builder $inner) use ($types): void {
                    foreach ($types as $type) {
                        $inner->orWhereJsonContains('employment_type', $type);
                    }
                });
            })
            ->when($dto->salaryMin, fn(Builder $q, int $min): Builder => $q->where('salary_from', '>=', $min))
            ->when($dto->salaryMax, fn(Builder $q, int $max): Builder => $q->where('salary_to', '<=', $max))
            ->when($dto->languages, function (Builder $q, array $languages): void {
                foreach ($languages as $lang) {
                    $q->whereJsonContains('languages', $lang);
                }
            })
            ->when($dto->suitability, function (Builder $q, array $suitability): void {
                foreach ($suitability as $item) {
                    $q->whereJsonContains('suitability', $item);
                }
            })
            ->when($dto->cityId, fn(Builder $q, int $id): Builder => $q->where('city_id', $id))
            ->orderByDesc('is_top')
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate($dto->perPage);
    }
}
