<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vacancy;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

final class PaymentService
{
    private const FEATURED_DAYS = 30;
    private const FEATURED_PRICE_CENTS = 1000; // $10.00

    public function __construct()
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session for promoting a vacancy.
     */
    public function createVacancyPromoCheckout(Vacancy $vacancy): string
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => [
                        'name'        => 'Premium Listing — 30 days',
                        'description' => "Promoted vacancy: {$vacancy->title}",
                    ],
                    'unit_amount'  => self::FEATURED_PRICE_CENTS,
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('payment.cancel'),
            'metadata'    => ['vacancy_id' => $vacancy->id],
        ]);

        return $session->url;
    }

    /**
     * Handle incoming Stripe webhook.
     *
     * @throws SignatureVerificationException
     * @throws UnexpectedValueException
     */
    public function handleWebhook(string $payload, string $signature): void
    {
        $event = Webhook::constructEvent(
            $payload,
            $signature,
            (string) config('services.stripe.webhook_secret')
        );

        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $session */
            $session   = $event->data->object;
            $vacancyId = (int) ($session->metadata->vacancy_id ?? 0);

            if ($vacancyId) {
                Vacancy::where('id', $vacancyId)->update([
                    'is_featured'    => true,
                    'featured_until' => now()->addDays(self::FEATURED_DAYS),
                ]);
            }
        }
    }
}
