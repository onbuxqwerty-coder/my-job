<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Models\Vacancy;
use App\Payments\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PaymentTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow('2025-06-15 12:00:00');
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    protected function makeVacancy(): Vacancy
    {
        return Vacancy::factory()->active(daysLeft: 5)->create();
    }

    protected function buildOrderId(Vacancy $vacancy, int $days = 30): string
    {
        return CheckoutService::buildOrderId($vacancy->id, $days);
    }
}
