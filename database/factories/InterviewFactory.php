<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'created_by'     => User::factory()->employer(),
            'scheduled_at'   => fake()->dateTimeBetween('+1 day', '+30 days'),
            'duration'       => fake()->randomElement([30, 45, 60, 90]),
            'type'           => fake()->randomElement(InterviewType::cases()),
            'meeting_link'   => null,
            'office_address' => null,
            'notes'          => fake()->optional()->sentence(),
            'internal_notes' => null,
            'status'         => InterviewStatus::Scheduled,
            'confirm_token'  => fake()->uuid(),
            'cancelled_reason' => null,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'status'       => InterviewStatus::Scheduled,
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status'       => InterviewStatus::Scheduled,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'           => InterviewStatus::Cancelled,
            'cancelled_reason' => fake()->sentence(),
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'         => InterviewType::Video,
            'meeting_link' => fake()->url(),
        ]);
    }
}
