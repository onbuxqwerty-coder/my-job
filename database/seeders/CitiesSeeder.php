<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('docs/ukr-cities.json');
        $content = file_get_contents($path);

        // Strip UTF-8 BOM if present
        $content = ltrim($content, "\xEF\xBB\xBF");

        $cities = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $now = now();

        City::truncate();

        foreach (array_chunk($cities, 100) as $chunk) {
            City::insertOrIgnore(array_map(fn(array $city) => [
                'slug'             => $city['id'],
                'name'             => $city['name'],
                'region'           => $city['region'],
                'is_region_center' => $city['is_region_center'],
                'created_at'       => $now,
                'updated_at'       => $now,
            ], $chunk));
        }

        $this->command->info('Cities seeded: ' . count($cities));
    }
}
