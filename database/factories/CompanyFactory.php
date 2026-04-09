<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id' => User::factory()->employer(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'logo' => null,
            'description' => fake()->paragraphs(3, true),
            'website' => fake()->optional()->url(),
            'location' => fake()->city(),
        ];
    }
}
