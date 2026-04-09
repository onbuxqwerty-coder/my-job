<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'IT', 'Sales', 'Construction', 'Healthcare', 'Marketing',
            'Finance', 'Education', 'Logistics', 'Legal', 'Design',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => null,
        ];
    }
}
