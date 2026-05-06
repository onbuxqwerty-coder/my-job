<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'plan_id'         => null,
            'payment_id'      => null,
            'status'          => 'pending',
            'payment_purpose' => 'Оплата замовлення №ORDER-' . fake()->unique()->randomNumber(5),
            'amount'          => fake()->randomElement([500.00, 1000.00, 2000.00]),
            'paid_at'         => null,
        ];
    }
}
