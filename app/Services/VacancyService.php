<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\VacancySearchDTO;
use App\Enums\PlanType;
use App\Enums\VacancyStatus;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class VacancyService
{
    public function publish(User $employer, array $data): Vacancy
    {
        $slug      = $this->generateSlug($data['title']);
        $expiresAt = $this->getExpiresAt($employer);

        $data['published_at'] ??= now();
        $data['status']       ??= VacancyStatus::Active;

        return Vacancy::create([...$data, 'slug' => $slug, 'expires_at' => $expiresAt]);
    }

    public function update(Vacancy $vacancy, array $data): Vacancy
    {
        $slug = isset($data['title']) && $data['title'] !== $vacancy->title
            ? $this->generateSlug($data['title'], $vacancy->id)
            : $vacancy->slug;

        $vacancy->update([...$data, 'slug' => $slug]);

        return $vacancy->refresh();
    }

    public function getExpiresAt(User $employer): Carbon
    {
        $plan = $employer->currentPlan();

        return match ($plan?->type) {
            PlanType::Business, PlanType::Pro => now()->addDays(60),
            default                           => now()->addDays(30),
        };
    }

    public function generateSlug(string $title, ?int $excludeId = null): string
    {
        $base    = Str::slug($title);
        $slug    = $base;
        $counter = 1;

        while (
            Vacancy::where('slug', $slug)
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }


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
