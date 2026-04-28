<?php

declare(strict_types=1);

namespace Tests\Feature\Stripe;

use App\Enums\VacancyStatus;
use App\Events\VacancyExtended;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

// Stripe вимкнено — тести деактивовано
// Для повторного увімкнення: розкоментуй StripeGateway в PaymentServiceProvider
// та встанови: composer require stripe/stripe-php
#[\PHPUnit\Framework\Attributes\Group('stripe')]
class WebhookExtendsVacancyTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.stripe.webhook_secret', $this->webhookSecret);
        \Illuminate\Support\Carbon::setTestNow('2025-06-15 12:00:00');
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Carbon::setTestNow();
        parent::tearDown();
    }

    private function makePayload(string $eventType, array $sessionData, ?string $eventId = null): array
    {
        return [
            'id'      => $eventId ?? 'evt_test_' . uniqid(),
            'object'  => 'event',
            'type'    => $eventType,
            'data'    => ['object' => $sessionData],
            'created' => time(),
        ];
    }

    private function signAndPost(array $payload): \Illuminate\Testing\TestResponse
    {
        $body      = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", $this->webhookSecret);
        $header    = "t={$timestamp},v1={$signature}";

        // Надсилаємо raw body, щоб підпис збігся (postJson re-encodes і ламає підпис)
        return $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE'         => 'application/json',
                'HTTP_Stripe-Signature' => $header,
            ],
            $body
        );
    }

    public function test_checkout_session_completed_extends_vacancy(): void
    {
        Event::fake();

        $vacancy    = Vacancy::factory()->active(daysLeft: 1)->create();
        $oldExpires = $vacancy->expires_at->copy();

        $payload = $this->makePayload('checkout.session.completed', [
            'id'             => 'cs_test_123',
            'object'         => 'checkout.session',
            'payment_status' => 'paid',
            'amount_total'   => 20000,
            'currency'       => 'uah',
            'metadata'       => [
                'type'       => 'vacancy_extension',
                'vacancy_id' => (string) $vacancy->id,
                'days'       => '30',
            ],
        ]);

        $this->signAndPost($payload)->assertOk();

        $fresh = $vacancy->fresh();
        $this->assertSame(VacancyStatus::Active, $fresh->status);
        $this->assertSame(
            $oldExpires->addDays(30)->toIso8601String(),
            $fresh->expires_at->toIso8601String()
        );

        Event::assertDispatched(
            VacancyExtended::class,
            fn ($e) => $e->vacancy->id === $vacancy->id && $e->days === 30
        );
    }

    public function test_duplicate_event_id_is_ignored(): void
    {
        $vacancy  = Vacancy::factory()->active(daysLeft: 10)->create();
        $eventId  = 'evt_duplicate_123';

        $payload = $this->makePayload('checkout.session.completed', [
            'id'             => 'cs_test_dup',
            'object'         => 'checkout.session',
            'payment_status' => 'paid',
            'amount_total'   => 20000,
            'currency'       => 'uah',
            'metadata'       => [
                'type'       => 'vacancy_extension',
                'vacancy_id' => (string) $vacancy->id,
                'days'       => '30',
            ],
        ], $eventId);

        $this->signAndPost($payload)->assertOk()->assertJson(['status' => 'ok']);

        $firstExpires = $vacancy->fresh()->expires_at->copy();

        $this->signAndPost($payload)
            ->assertOk()
            ->assertJson(['status' => 'duplicate']);

        $this->assertSame(
            $firstExpires->toIso8601String(),
            $vacancy->fresh()->expires_at->toIso8601String()
        );
    }

    public function test_invalid_signature_returns_400(): void
    {
        $this->postJson(
            '/stripe/webhook',
            ['fake' => 'data'],
            ['Stripe-Signature' => 'invalid']
        )->assertStatus(400);
    }

    public function test_event_without_metadata_type_does_not_dispatch_vacancy_extended(): void
    {
        Event::fake();

        $payload = $this->makePayload('checkout.session.completed', [
            'id'             => 'cs_no_meta',
            'payment_status' => 'paid',
            'metadata'       => ['something' => 'else'],
        ]);

        $this->signAndPost($payload)->assertOk();

        Event::assertNotDispatched(VacancyExtended::class);
    }

    public function test_archived_vacancy_is_not_extended_and_event_not_dispatched(): void
    {
        Event::fake();

        $vacancy = Vacancy::factory()->archived()->create();

        $payload = $this->makePayload('checkout.session.completed', [
            'id'             => 'cs_archived',
            'object'         => 'checkout.session',
            'payment_status' => 'paid',
            'amount_total'   => 20000,
            'currency'       => 'uah',
            'metadata'       => [
                'type'       => 'vacancy_extension',
                'vacancy_id' => (string) $vacancy->id,
                'days'       => '30',
            ],
        ]);

        $this->signAndPost($payload)->assertOk();

        $this->assertSame(VacancyStatus::Archived, $vacancy->fresh()->status);
        Event::assertNotDispatched(VacancyExtended::class);
    }
}
