<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Enums\VacancyStatus;
use App\Events\VacancyExtended;
use Illuminate\Support\Facades\Event;

class WayForPayGatewayTest extends PaymentTestCase
{
    private string $merchantPassword = 'test_secret_password';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'payments.gateways.wayforpay.merchant_account'  => 'test_merchant',
            'payments.gateways.wayforpay.merchant_password' => $this->merchantPassword,
            'payments.gateways.wayforpay.merchant_domain'   => 'example.com',
        ]);
    }

    public function test_invalid_signature_returns_400(): void
    {
        $this->postJson('/webhooks/payments/wayforpay', [
            'merchantAccount'   => 'test_merchant',
            'orderReference'    => 'vac_1_30_abc123',
            'amount'            => 200.00,
            'currency'          => 'UAH',
            'authCode'          => '',
            'cardPan'           => '',
            'transactionStatus' => 'Approved',
            'reasonCode'        => '1100',
            'merchantSignature' => 'wrong_signature',
        ])->assertStatus(400);
    }

    public function test_approved_transaction_extends_vacancy(): void
    {
        Event::fake();

        $vacancy    = $this->makeVacancy();
        $orderId    = $this->buildOrderId($vacancy, 30);
        $oldExpires = $vacancy->expires_at->copy();

        $this->postJson('/webhooks/payments/wayforpay', $this->buildPayload($orderId, 'Approved', 200.00))
            ->assertOk();

        $this->assertTrue($vacancy->fresh()->expires_at->gt($oldExpires));
        Event::assertDispatched(VacancyExtended::class, fn ($e) => $e->days === 30);
    }

    public function test_declined_transaction_is_ignored(): void
    {
        Event::fake();

        $vacancy = $this->makeVacancy();
        $orderId = $this->buildOrderId($vacancy, 30);

        $this->postJson('/webhooks/payments/wayforpay', $this->buildPayload($orderId, 'Declined', 0.00))
            ->assertOk();

        $this->assertSame(VacancyStatus::Active, $vacancy->fresh()->status);
        Event::assertNotDispatched(VacancyExtended::class);
    }

    public function test_response_contains_accept_with_signature(): void
    {
        $vacancy = $this->makeVacancy();
        $orderId = $this->buildOrderId($vacancy, 30);

        $response = $this->postJson('/webhooks/payments/wayforpay', $this->buildPayload($orderId, 'Approved', 200.00))
            ->assertOk();

        $json = $response->json();
        $this->assertSame('accept', $json['status']);
        $this->assertArrayHasKey('signature', $json);
        $this->assertSame($orderId, $json['orderReference']);
    }

    // =========================================================================

    /**
     * Будує WFP webhook payload з правильним HMAC-MD5 підписом.
     * Порядок полів точно збігається з WayForPayGateway::verifyHmacSignature().
     */
    private function buildPayload(string $orderId, string $status, float $amount): array
    {
        $fields = [
            'test_merchant', // merchantAccount
            $orderId,        // orderReference
            (string) $amount,
            'UAH',           // currency
            '',              // authCode
            '',              // cardPan
            $status,         // transactionStatus
            '1100',          // reasonCode
        ];

        $signature = hash_hmac('md5', implode(';', $fields), $this->merchantPassword);

        return [
            'merchantAccount'   => 'test_merchant',
            'orderReference'    => $orderId,
            'amount'            => $amount,
            'currency'          => 'UAH',
            'authCode'          => '',
            'cardPan'           => '',
            'transactionStatus' => $status,
            'reasonCode'        => '1100',
            'merchantSignature' => $signature,
        ];
    }
}
