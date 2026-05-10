<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Vacancy;

final class ProfileCompletenessService
{
    /**
     * @return array{score: int, next_step: array{label: string, url: string}|null, missing: list<array{field: string, label: string, weight: int}>}
     */
    public function candidateScore(User $user): array
    {
        $resume = $user->resumes()->latest()->first();

        $info     = $resume?->personal_info ?? [];
        $location = $resume?->location ?? [];

        $fields = [
            [
                'field'  => 'first_name',
                'label'  => 'Ім\'я',
                'weight' => 10,
                'filled' => ! empty($info['first_name']),
                'url'    => '/seeker/resumes',
            ],
            [
                'field'  => 'last_name',
                'label'  => 'Прізвище',
                'weight' => 10,
                'filled' => ! empty($info['last_name']),
                'url'    => '/seeker/resumes',
            ],
            [
                'field'  => 'position',
                'label'  => 'Бажана посада',
                'weight' => 10,
                'filled' => ! empty($info['position']),
                'url'    => '/seeker/resumes',
            ],
            [
                'field'  => 'phone',
                'label'  => 'Телефон',
                'weight' => 10,
                'filled' => ! empty($info['phone']),
                'url'    => '/seeker/resumes',
            ],
            [
                'field'  => 'city',
                'label'  => 'Місто',
                'weight' => 10,
                'filled' => ! empty($location['city']),
                'url'    => '/seeker/resumes',
            ],
            [
                'field'  => 'experience',
                'label'  => 'Досвід роботи',
                'weight' => 30,
                'filled' => $resume !== null && $resume->experiences()->exists(),
                'url'    => '/seeker/resumes',
            ],
            [
                'field'  => 'skills',
                'label'  => 'Навички (мінімум 3)',
                'weight' => 20,
                'filled' => $resume !== null && $resume->skills()->count() >= 3,
                'url'    => '/seeker/resumes',
            ],
        ];

        return $this->buildResult($fields);
    }

    /**
     * @return array{score: int, next_step: array{label: string, url: string}|null, missing: list<array{field: string, label: string, weight: int}>}
     */
    public function employerScore(User $user): array
    {
        $company = $user->company;

        $fields = [
            [
                'field'  => 'name',
                'label'  => 'Назва компанії',
                'weight' => 20,
                'filled' => $company !== null && ! empty($company->name),
                'url'    => '/employer/profile',
            ],
            [
                'field'  => 'logo',
                'label'  => 'Логотип',
                'weight' => 15,
                'filled' => $company !== null && ! empty($company->logo),
                'url'    => '/employer/profile',
            ],
            [
                'field'  => 'description',
                'label'  => 'Опис компанії',
                'weight' => 25,
                'filled' => $company !== null && ! empty($company->description),
                'url'    => '/employer/profile',
            ],
            [
                'field'  => 'website',
                'label'  => 'Веб-сайт',
                'weight' => 15,
                'filled' => $company !== null && ! empty($company->website),
                'url'    => '/employer/profile',
            ],
            [
                'field'  => 'city',
                'label'  => 'Місто',
                'weight' => 15,
                'filled' => $company !== null && ($company->city_id !== null || ! empty($company->location)),
                'url'    => '/employer/profile',
            ],
            [
                'field'  => 'phone',
                'label'  => 'Телефон',
                'weight' => 10,
                'filled' => ! empty($user->phone),
                'url'    => '/employer/profile',
            ],
        ];

        return $this->buildResult($fields);
    }

    /**
     * @return array{score: int, next_step: array{label: string, url: string}|null, missing: list<array{field: string, label: string, weight: int}>}
     */
    public function vacancyScore(Vacancy $vacancy): array
    {
        $isRemote = ! empty($vacancy->employment_type) && (
            in_array('remote', (array) $vacancy->employment_type, true) ||
            in_array('hybrid', (array) $vacancy->employment_type, true)
        );

        $editUrl = "/employer/vacancies/{$vacancy->id}/edit";

        $fields = [
            [
                'field'  => 'title',
                'label'  => 'Назва вакансії',
                'weight' => 15,
                'filled' => ! empty($vacancy->title),
                'url'    => $editUrl,
            ],
            [
                'field'  => 'description',
                'label'  => 'Опис (мін. 200 символів)',
                'weight' => 25,
                'filled' => mb_strlen(strip_tags($vacancy->description ?? '')) >= 200,
                'url'    => $editUrl,
            ],
            [
                'field'  => 'salary',
                'label'  => 'Зарплата',
                'weight' => 15,
                'filled' => $vacancy->salary_from !== null,
                'url'    => $editUrl,
            ],
            [
                'field'  => 'location',
                'label'  => 'Місто або remote',
                'weight' => 10,
                'filled' => $vacancy->city_id !== null || $isRemote,
                'url'    => $editUrl,
            ],
            [
                'field'  => 'employment_type',
                'label'  => 'Тип зайнятості',
                'weight' => 10,
                'filled' => ! empty($vacancy->employment_type),
                'url'    => $editUrl,
            ],
            [
                'field'  => 'skills',
                'label'  => 'Навички',
                'weight' => 15,
                'filled' => $vacancy->skills()->exists(),
                'url'    => $editUrl,
            ],
            [
                'field'  => 'category',
                'label'  => 'Категорія',
                'weight' => 10,
                'filled' => $vacancy->category_id !== null,
                'url'    => $editUrl,
            ],
        ];

        return $this->buildResult($fields);
    }

    /**
     * @param  array<int, array{field: string, label: string, weight: int, filled: bool, url: string}>  $fields
     * @return array{score: int, next_step: array{label: string, url: string}|null, missing: list<array{field: string, label: string, weight: int}>}
     */
    private function buildResult(array $fields): array
    {
        $score   = 0;
        $missing = [];

        foreach ($fields as $field) {
            if ($field['filled']) {
                $score += $field['weight'];
            } else {
                $missing[] = [
                    'field'  => $field['field'],
                    'label'  => $field['label'],
                    'weight' => $field['weight'],
                    'url'    => $field['url'],
                ];
            }
        }

        $nextStep = null;

        if (! empty($missing)) {
            $topMissing = collect($missing)
                ->sortByDesc('weight')
                ->first();

            $nextStep = [
                'label' => $topMissing['label'],
                'url'   => $topMissing['url'],
            ];
        }

        $missingOut = array_map(
            fn (array $m) => ['field' => $m['field'], 'label' => $m['label'], 'weight' => $m['weight']],
            $missing
        );

        return [
            'score'     => $score,
            'next_step' => $nextStep,
            'missing'   => array_values($missingOut),
        ];
    }
}
