<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Str;

final class PendingVacancyService
{
    public function hasPending(): bool
    {
        return session()->has('pending_vacancy');
    }

    /**
     * Створює вакансію з session('pending_vacancy').
     * Якщо у employer немає компанії — автоматично створює її.
     * Повертає створену Vacancy або null якщо умови не виконані.
     */
    public function createFromSession(User $user): ?Vacancy
    {
        if (! $this->hasPending() || $user->role !== UserRole::Employer) {
            return null;
        }

        $company = $user->company ?? $this->autoCreateCompany($user);
        $data    = session()->pull('pending_vacancy');

        return Vacancy::create([
            'company_id'      => $company->id,
            'category_id'     => $data['category_id'],
            'city_id'         => $data['city_id'],
            'title'           => $data['title'],
            'slug'            => Str::slug($data['title']) . '-' . Str::random(6),
            'salary_from'     => $data['salary_from'] ?? null,
            'description'     => '',
            'salary_to'       => null,
            'currency'        => 'UAH',
            'employment_type' => ['full-time'],
            'is_active'       => true,
            'is_featured'     => false,
            'is_top'          => false,
            'languages'       => [],
            'suitability'     => [],
        ]);
    }

    private function autoCreateCompany(User $user): Company
    {
        $slug = 'kompaniya-' . $user->id . '-' . Str::random(4);

        return Company::create([
            'user_id'     => $user->id,
            'name'        => 'Компанія',
            'slug'        => $slug,
            'description' => '',
            'is_verified' => false,
        ]);
    }
}
