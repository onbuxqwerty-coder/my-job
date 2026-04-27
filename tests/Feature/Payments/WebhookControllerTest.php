<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTOs\CheckoutData;
use App\Payments\DTOs\PaymentResult;
use App\Payments\Exceptions\InvalidWebhookSignatureException;
use Illuminate\Http\Request;

class WebhookControllerTest extends PaymentTestCase
{
    public function test_unknown_gateway_returns_404(): void
    {
        $this->post('/webhooks/payments/unknown_provider')
            ->assertStatus(404);
    }

    public function test_idempotency_prevents_double_extension(): void
    {
        $vacancy = $this->makeVacancy();
        $orderId = $this->buildOrderId($vacancy, 30);

        $this->mockGateway('mono', new PaymentResult(
            isPaid:          true,
            gatewayName:     'mono',
            externalEventId: 'mono_evt_dup_test',
            orderId:         $orderId,
            amountKopecks:   20000,
            currency:        'UAH',
            vacancyId:       (string) $vacancy->id,
            days:            30,
        ));

        $this->post('/webhooks/payments/mono', [])->assertOk();

        $firstExpires = $vacancy->fresh()->expires_at->copy();

        $this->post('/webhooks/payments/mono', [])->assertOk();

        $this->assertSame(
            $firstExpires->toIso8601String(),
            $vacancy->fresh()->expires_at->toIso8601String(),
        );
    }

    private function mockGateway(string $name, PaymentResult $result): void
    {
        $gateway = new class($name, $result) implements PaymentGateway {
            public function __construct(
                private string $gatewayName,
                private PaymentResult $paymentResult,
            ) {}

            public function name(): string { return $this->gatewayName; }

            public function createCheckout(CheckoutData $data): string { return ''; }

            public function parseWebhook(Request $request): PaymentResult
            {
                return $this->paymentResult;
            }

            public function successResponse(): \Illuminate\Http\Response
            {
                return response('');
            }
        };

        app(\App\Http\Controllers\Payments\PaymentGatewayRegistry::class)->register($gateway);
    }
}
