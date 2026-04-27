<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Events\VacancyExtended;
use App\Enums\VacancyStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

class MonoPayGatewayTest extends PaymentTestCase
{
    private string $privateKey = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('sodium extension is required for MonoPay Ed25519 tests');
        }

        $keyPair          = sodium_crypto_sign_keypair();
        $this->privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKeyB64     = base64_encode(sodium_crypto_sign_publickey($keyPair));

        config([
            'payments.gateways.mono.token'      => 'test_mono_token',
            'payments.gateways.mono.public_key' => $publicKeyB64,
        ]);
    }

    public function test_webhook_missing_sign_header_returns_400(): void
    {
        // Відсутній X-Sign header → InvalidWebhookSignatureException → 400
        $this->postMono(json_encode(['invoiceId' => 'x']), null)
            ->assertStatus(400);
    }

    public function test_webhook_invalid_signature_returns_400(): void
    {
        // 'invalid!!' — не є валідним base64 → decode повертає false → 400
        $body = json_encode(['invoiceId' => 'x', 'status' => 'success']);
        $this->postMono($body, 'invalid_base64!!')->assertStatus(400);
    }

    public function test_not_paid_status_is_ignored(): void
    {
        Event::fake();

        $vacancy = $this->makeVacancy();
        $orderId = $this->buildOrderId($vacancy, 30);
        $body    = json_encode([
            'invoiceId' => 'inv_fail_001',
            'status'    => 'failure',
            'reference' => $orderId,
            'amount'    => 20000,
        ]);

        $this->postMono($body, $this->sign($body))->assertOk();

        Event::assertNotDispatched(VacancyExtended::class);
        $this->assertSame(VacancyStatus::Active, $vacancy->fresh()->status);
    }

    public function test_successful_webhook_extends_vacancy(): void
    {
        Event::fake();

        $vacancy    = $this->makeVacancy();
        $orderId    = $this->buildOrderId($vacancy, 30);
        $oldExpires = $vacancy->expires_at->copy();

        $body = json_encode([
            'invoiceId' => 'inv_success_001',
            'status'    => 'success',
            'reference' => $orderId,
            'amount'    => 20000,
        ]);

        $this->postMono($body, $this->sign($body))->assertOk();

        $this->assertTrue($vacancy->fresh()->expires_at->gt($oldExpires));
        Event::assertDispatched(VacancyExtended::class, fn ($e) =>
            $e->vacancy->id === $vacancy->id && $e->days === 30
        );
    }

    public function test_duplicate_invoice_is_ignored(): void
    {
        Event::fake();

        $vacancy = $this->makeVacancy();
        $orderId = $this->buildOrderId($vacancy, 30);
        $body    = json_encode([
            'invoiceId' => 'inv_idempotency_001',
            'status'    => 'success',
            'reference' => $orderId,
            'amount'    => 20000,
        ]);
        $sign = $this->sign($body);

        $this->postMono($body, $sign)->assertOk();
        $firstExpires = $vacancy->fresh()->expires_at->copy();

        $this->postMono($body, $sign)->assertOk();

        $this->assertSame(
            $firstExpires->toIso8601String(),
            $vacancy->fresh()->expires_at->toIso8601String(),
        );
        Event::assertDispatchedTimes(VacancyExtended::class, 1);
    }

    // =========================================================================

    /**
     * Надсилає raw JSON body з X-Sign header — точно як MonoPay надсилає webhook.
     * $sign === null означає відсутній заголовок (тест missing header).
     */
    private function postMono(string $body, ?string $sign): TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($sign !== null) {
            $server['HTTP_X_SIGN'] = $sign;
        }

        return $this->call('POST', '/webhooks/payments/mono', [], [], [], $server, $body);
    }

    private function sign(string $body): string
    {
        return base64_encode(sodium_crypto_sign_detached($body, $this->privateKey));
    }
}
