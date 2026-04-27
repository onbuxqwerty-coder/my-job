<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Events\VacancyExtended;
use Illuminate\Support\Facades\Event;

class LiqPayGatewayTest extends PaymentTestCase
{
    private string $publicKey  = 'sandbox_test_public_key';
    private string $privateKey = 'sandbox_test_private_key';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'payments.gateways.liqpay.public_key'  => $this->publicKey,
            'payments.gateways.liqpay.private_key' => $this->privateKey,
        ]);
    }

    public function test_missing_data_returns_400(): void
    {
        $this->post('/webhooks/payments/liqpay', [])->assertStatus(400);
    }

    public function test_invalid_signature_returns_400(): void
    {
        $data = base64_encode(json_encode(['status' => 'success', 'order_id' => 'x']));

        $this->post('/webhooks/payments/liqpay', [
            'data'      => $data,
            'signature' => 'definitely_wrong_signature',
        ])->assertStatus(400);
    }

    public function test_success_status_extends_vacancy(): void
    {
        Event::fake();

        $vacancy    = $this->makeVacancy();
        $orderId    = $this->buildOrderId($vacancy, 30);
        $oldExpires = $vacancy->expires_at->copy();

        [$data, $sig] = $this->buildWebhook($orderId, 'success');

        $this->post('/webhooks/payments/liqpay', ['data' => $data, 'signature' => $sig])
            ->assertOk();

        $this->assertTrue($vacancy->fresh()->expires_at->gt($oldExpires));
        Event::assertDispatched(VacancyExtended::class, fn ($e) => $e->days === 30);
    }

    public function test_sandbox_status_is_treated_as_success(): void
    {
        Event::fake();

        $vacancy = $this->makeVacancy();
        $orderId = $this->buildOrderId($vacancy, 15);

        [$data, $sig] = $this->buildWebhook($orderId, 'sandbox', 100.00);

        $this->post('/webhooks/payments/liqpay', ['data' => $data, 'signature' => $sig])
            ->assertOk();

        Event::assertDispatched(VacancyExtended::class, fn ($e) => $e->days === 15);
    }

    public function test_failure_status_is_ignored(): void
    {
        Event::fake();

        $vacancy = $this->makeVacancy();
        $orderId = $this->buildOrderId($vacancy, 30);

        [$data, $sig] = $this->buildWebhook($orderId, 'failure');

        $this->post('/webhooks/payments/liqpay', ['data' => $data, 'signature' => $sig])
            ->assertOk();

        Event::assertNotDispatched(VacancyExtended::class);
    }

    public function test_reversed_status_does_not_revert_expiry(): void
    {
        Event::fake();

        $vacancy = $this->makeVacancy();
        $orderId = $this->buildOrderId($vacancy, 30);

        // 1. Успішна оплата
        [$d1, $s1] = $this->buildWebhook($orderId, 'success', 200.00, 'pay_001');
        $this->post('/webhooks/payments/liqpay', ['data' => $d1, 'signature' => $s1])->assertOk();

        $extendedExpires = $vacancy->fresh()->expires_at->copy();

        // 2. Повернення (reversed) — різний payment_id, тому idempotency не спрацьовує
        [$d2, $s2] = $this->buildWebhook($orderId, 'reversed', 200.00, 'pay_001_rev');
        $this->post('/webhooks/payments/liqpay', ['data' => $d2, 'signature' => $s2])->assertOk();

        // expires_at не змінилась (reversed = isPaid false = ігнорується)
        $this->assertSame(
            $extendedExpires->toIso8601String(),
            $vacancy->fresh()->expires_at->toIso8601String(),
        );
    }

    // =========================================================================

    /** @return array{0: string, 1: string} [base64Data, signature] */
    private function buildWebhook(
        string $orderId,
        string $status,
        float $amount = 200.00,
        ?string $paymentId = null,
    ): array {
        $params = [
            'public_key' => $this->publicKey,
            'version'    => '3',
            'action'     => 'pay',
            'payment_id' => $paymentId ?? 'liqpay_' . uniqid(),
            'status'     => $status,
            'amount'     => $amount,
            'currency'   => 'UAH',
            'order_id'   => $orderId,
        ];

        $data      = base64_encode(json_encode($params, JSON_UNESCAPED_UNICODE));
        $signature = base64_encode(sha1($this->privateKey . $data . $this->privateKey, true));

        return [$data, $signature];
    }
}
