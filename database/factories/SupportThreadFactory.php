<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactRole;
use App\Enums\SupportThreadStatus;
use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportThread>
 */
class SupportThreadFactory extends Factory
{
    protected $model = SupportThread::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'subject'         => fake()->sentence(5),
            'role'            => ContactRole::Seeker,
            'status'          => SupportThreadStatus::Open,
            'last_message_at' => now(),
        ];
    }
}
