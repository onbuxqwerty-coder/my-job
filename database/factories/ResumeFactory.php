<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resume>
 */
class ResumeFactory extends Factory
{
    protected $model = Resume::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'title'        => $this->faker->sentence(3),
            'status'       => 'draft',
            'personal_info' => [
                'first_name'        => null,
                'last_name'         => null,
                'email'             => null,
                'email_verified_at' => null,
                'phone'             => null,
                'privacy'           => false,
                'transparency'      => false,
            ],
            'location' => [
                'city'                => null,
                'city_id'             => null,
                'street'              => null,
                'building'            => null,
                'latitude'            => null,
                'longitude'           => null,
                'no_location_binding' => false,
            ],
            'notifications' => [
                'site'     => true,
                'email'    => false,
                'sms'      => false,
                'telegram' => false,
                'viber'    => false,
                'whatsapp' => false,
            ],
            'additional_info' => [
                'social_links' => [],
                'hobbies'      => [],
                'bio'          => null,
            ],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function withPersonalInfo(): static
    {
        return $this->state(fn (array $attributes) => [
            'personal_info' => [
                'first_name'        => $this->faker->firstName(),
                'last_name'         => $this->faker->lastName(),
                'email'             => $this->faker->safeEmail(),
                'email_verified_at' => now()->toIso8601String(),
                'phone'             => null,
                'privacy'           => false,
                'transparency'      => false,
            ],
        ]);
    }
}
