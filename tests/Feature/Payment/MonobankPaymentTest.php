<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Jobs\ProcessMonobankPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonobankPaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function webhook_dispatches_job(): void
    {
        Queue::fake();

        $payload = [
            'data' => [
                'statementItem' => [
                    'id'          => 'stmt_abc123',
                    'amount'      => 50000,
                    'description' => 'Оплата замовлення №ORDER-1',
                    'status'      => 'DONE',
                    'time'        => now()->timestamp,
                ],
            ],
        ];

        $this->postJson(route('mono.webhook'), $payload)
             ->assertStatus(200);

        Queue::assertPushed(ProcessMonobankPayment::class);
    }

    #[Test]
    public function job_marks_order_as_paid(): void
    {
        $user  = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status'  => 'pending',
            'amount'  => 500.00,
        ]);

        $statement = [
            'id'          => 'stmt_unique_001',
            'amount'      => 50000,
            'status'      => 'DONE',
            'description' => 'Оплата замовлення №ORDER-' . $order->id,
            'time'        => now()->timestamp,
        ];

        (new ProcessMonobankPayment($statement))->handle();

        $this->assertDatabaseHas('orders', [
            'id'         => $order->id,
            'status'     => 'paid',
            'payment_id' => 'stmt_unique_001',
        ]);
    }

    #[Test]
    public function duplicate_statement_is_ignored(): void
    {
        $user  = User::factory()->create();
        $order = Order::factory()->create([
            'user_id'    => $user->id,
            'status'     => 'paid',
            'amount'     => 500.00,
            'payment_id' => 'stmt_dup_001',
        ]);

        $statement = [
            'id'          => 'stmt_dup_001',
            'amount'      => 50000,
            'status'      => 'DONE',
            'description' => 'Оплата замовлення №ORDER-' . $order->id,
            'time'        => now()->timestamp,
        ];

        (new ProcessMonobankPayment($statement))->handle();
        (new ProcessMonobankPayment($statement))->handle();

        $this->assertDatabaseCount('orders', 1);
    }

    #[Test]
    public function amount_mismatch_does_not_mark_order_paid(): void
    {
        $user  = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status'  => 'pending',
            'amount'  => 500.00,
        ]);

        $statement = [
            'id'          => 'stmt_wrong_amount',
            'amount'      => 10000,
            'status'      => 'DONE',
            'description' => 'Оплата замовлення №ORDER-' . $order->id,
            'time'        => now()->timestamp,
        ];

        (new ProcessMonobankPayment($statement))->handle();

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'pending',
        ]);
    }
}
