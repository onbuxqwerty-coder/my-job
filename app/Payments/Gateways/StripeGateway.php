<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Payments\CheckoutService;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTOs\CheckoutData;
use App\Payments\DTOs\PaymentResult;
use App\Payments\Exceptions\InvalidWebhookSignatureException;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'stripe';
    }

    public function createCheckout(CheckoutData $data): string
    {
        Stripe::setApiKey(config('payments.gateways.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => strtolower($data->currency),
                    'unit_amount'  => $data->amountMinorUnits(),
                    'product_data' => ['name' => $data->description],
                ],
                'quantity' => 1,
            ]],
            'mode'                 => 'payment',
            'success_url'          => $data->successUrl,
            'cancel_url'           => $data->cancelUrl,
            'client_reference_id'  => $data->orderId,
            'metadata' => [
                'type'       => 'vacancy_extension',
                'vacancy_id' => (string) $data->vacancy->id,
                'days'       => (string) $data->days,
                'order_id'   => $data->orderId,
            ],
        ]);

        return $session->url;
    }

    public function parseWebhook(Request $request): PaymentResult
    {
        try {
            $event = Webhook::constructEvent(
                payload:   $request->getContent(),
                sigHeader: $request->header('Stripe-Signature', ''),
                secret:    config('payments.gateways.stripe.webhook_secret'),
                tolerance: 300,
            );
        } catch (SignatureVerificationException $e) {
            throw new InvalidWebhookSignatureException($e->getMessage());
        }

        if ($event->type !== 'checkout.session.completed') {
            return new PaymentResult(
                isPaid:          false,
                gatewayName:     $this->name(),
                externalEventId: $event->id,
                orderId:         '',
                amountKopecks:   0,
                currency:        'UAH',
                vacancyId:       null,
                days:            null,
                failureReason:   "Unhandled event type: {$event->type}",
            );
        }

        /** @var Session $session */
        $session = $event->data->object;

        if ($session->payment_status !== 'paid') {
            return new PaymentResult(
                isPaid:          false,
                gatewayName:     $this->name(),
                externalEventId: $event->id,
                orderId:         $session->client_reference_id ?? '',
                amountKopecks:   0,
                currency:        'UAH',
                vacancyId:       null,
                days:            null,
                failureReason:   "payment_status={$session->payment_status}",
            );
        }

        $orderId = $session->client_reference_id ?? ($session->metadata->order_id ?? '');
        [$vacancyId, $days] = CheckoutService::parseOrderId($orderId);

        return new PaymentResult(
            isPaid:          true,
            gatewayName:     $this->name(),
            externalEventId: $event->id,
            orderId:         $orderId,
            amountKopecks:   (int) $session->amount_total,
            currency:        strtoupper((string) $session->currency),
            vacancyId:       $vacancyId ? (string) $vacancyId : null,
            days:            $days,
        );
    }

    public function successResponse(): \Illuminate\Http\Response
    {
        return response(json_encode(['status' => 'ok']), 200, ['Content-Type' => 'application/json']);
    }
}
