<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vacancy_id' => Vacancy::factory(),
            'user_id' => User::factory(),
            'resume_url' => fake()->url(),
            'cover_letter' => fake()->optional()->paragraphs(2, true),
            'status' => ApplicationStatus::Pending,
        ];
    }
}
