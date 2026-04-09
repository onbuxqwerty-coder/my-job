<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmploymentType;
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
            'company_id' => Company::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->paragraphs(5, true),
            'salary_from' => $salaryFrom,
            'salary_to' => $salaryFrom ? $salaryFrom + fake()->numberBetween(5000, 30000) : null,
            'currency' => 'UAH',
            'employment_type' => fake()->randomElement(EmploymentType::cases()),
            'is_active' => true,
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
