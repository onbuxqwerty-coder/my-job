<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $categoryNames = [
            'IT', 'Sales', 'Marketing', 'Healthcare', 'Construction',
            'Finance', 'Education', 'Logistics', 'Legal', 'Design',
        ];

        $categories = collect($categoryNames)->map(
            fn(string $name) => Category::factory()->create([
                'name' => $name,
                'slug' => Str::slug($name),
            ])
        );

        $companies = Company::factory(5)->create();

        Vacancy::factory(50)
            ->recycle($companies)
            ->recycle($categories)
            ->create();
    }
}
