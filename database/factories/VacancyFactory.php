<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->jobTitle();
        $salaryFrom = fake()->optional(0.8)->numberBetween(10000, 50000);

        return [
            'company_id'                  => Company::factory(),
            'category_id'                 => Category::factory(),
            'title'                       => $title,
            'slug'                        => Str::slug($title) . '-' . Str::random(8),
            'description'                 => fake()->paragraphs(5, true),
            'salary_from'                 => $salaryFrom,
            'salary_to'                   => $salaryFrom ? $salaryFrom + fake()->numberBetween(5000, 30000) : null,
            'currency'                    => 'UAH',
            'employment_type'             => [fake()->randomElement(EmploymentType::cases())->value],
            'is_active'                   => false,
            'status'                      => VacancyStatus::Draft,
            'published_at'                => null,
            'expires_at'                  => null,
            'expiry_notification_sent_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state([
            'status'       => VacancyStatus::Draft,
            'is_active'    => false,
            'published_at' => null,
            'expires_at'   => null,
        ]);
    }

    public function active(int $daysLeft = 15): static
    {
        return $this->state([
            'status'       => VacancyStatus::Active,
            'is_active'    => true,
            'published_at' => now()->subDays(30 - $daysLeft),
            'expires_at'   => now()->addDays($daysLeft),
        ]);
    }

    public function expired(int $daysAgo = 5): static
    {
        return $this->state([
            'status'       => VacancyStatus::Expired,
            'is_active'    => false,
            'published_at' => now()->subDays(30 + $daysAgo),
            'expires_at'   => now()->subDays($daysAgo),
        ]);
    }

    public function expiringSoon(int $hours = 12): static
    {
        return $this->state([
            'status'       => VacancyStatus::Active,
            'is_active'    => true,
            'published_at' => now()->subDays(29),
            'expires_at'   => now()->addHours($hours),
        ]);
    }

    public function archived(): static
    {
        return $this->state([
            'status'       => VacancyStatus::Archived,
            'is_active'    => false,
            'published_at' => now()->subDays(60),
            'expires_at'   => now()->subDays(30),
        ]);
    }
}
