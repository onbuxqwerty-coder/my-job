<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\VacancyStatus;
use App\Events\VacancyExtended;
use App\Models\Vacancy;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

final class PaymentService
{
    private const FEATURED_DAYS        = 30;
    private const FEATURED_PRICE_CENTS = 1000;
    private const ALLOWED_EXTENSION_DAYS = [15, 30, 90];

    public function __construct()
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session for promoting a vacancy (featured).
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
            'metadata'    => [
                'type'       => 'vacancy_featured',
                'vacancy_id' => (string) $vacancy->id,
            ],
        ]);

        return $session->url;
    }

    /**
     * @return string 'ok' | 'duplicate'
     * @throws SignatureVerificationException
     * @throws UnexpectedValueException
     */
    public function handleWebhook(string $payload, string $signature): string
    {
        $event = Webhook::constructEvent(
            $payload,
            $signature,
            (string) config('services.stripe.webhook_secret')
        );

        // Idempotency — не обробляємо ту саму подію двічі
        if (DB::table('stripe_processed_events')->where('event_id', $event->id)->exists()) {
            Log::channel('payments')->info('Stripe webhook: duplicate event ignored', [
                'event_id'   => $event->id,
                'event_type' => $event->type,
            ]);
            return 'duplicate';
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var Session $session */
            $session = $event->data->object;
            $type    = $session->metadata->type ?? null;

            match ($type) {
                'vacancy_extension' => $this->handleVacancyExtension($event, $session),
                default             => $this->handleVacancyFeatured($session),
            };
        } else {
            Log::channel('payments')->info('Stripe webhook: unhandled event type', [
                'event_id'   => $event->id,
                'event_type' => $event->type,
            ]);
        }

        // Записуємо ПІСЛЯ успіху бізнес-логіки (CRITICAL #3)
        DB::table('stripe_processed_events')->insert([
            'event_id'     => $event->id,
            'event_type'   => $event->type,
            'processed_at' => now(),
        ]);

        return 'ok';
    }

    private function handleVacancyExtension(Event $event, Session $session): void
    {
        $logContext = [
            'event_id'   => $event->id,
            'session_id' => $session->id,
            'metadata'   => $session->metadata?->toArray() ?? [],
        ];

        if ($session->payment_status !== 'paid') {
            Log::channel('payments')->warning('Stripe webhook: session not paid yet', array_merge(
                $logContext,
                ['payment_status' => $session->payment_status],
            ));
            return;
        }

        $vacancyId = (int) ($session->metadata->vacancy_id ?? 0);
        $days      = (int) ($session->metadata->days ?? 0);

        if ($vacancyId <= 0 || ! in_array($days, self::ALLOWED_EXTENSION_DAYS, true)) {
            Log::channel('payments')->error('Stripe webhook: invalid metadata for vacancy_extension', array_merge(
                $logContext,
                ['vacancy_id' => $vacancyId, 'days' => $days],
            ));
            return;
        }

        DB::transaction(function () use ($vacancyId, $days, $session, $event, $logContext) {
            $vacancy = Vacancy::query()->lockForUpdate()->find($vacancyId);

            if (! $vacancy) {
                Log::channel('payments')->error('Stripe webhook: vacancy not found', array_merge(
                    $logContext,
                    ['vacancy_id' => $vacancyId],
                ));
                return;
            }

            if ($vacancy->status === VacancyStatus::Archived) {
                Log::channel('payments')->critical('Stripe webhook: REFUND REQUIRED — cannot extend archived vacancy', [
                    'session_id'     => $session->id,
                    'payment_intent' => $session->payment_intent,
                    'vacancy_id'     => $vacancy->id,
                    'amount'         => $session->amount_total,
                    'currency'       => $session->currency,
                ]);
                return;
            }

            $vacancy->extend($days);

            VacancyExtended::dispatch(
                vacancy: $vacancy,
                days: $days,
                amountCents: (int) $session->amount_total,
                currency: strtoupper((string) $session->currency),
                stripeEventId: $event->id,
            );

            Log::channel('payments')->info('Stripe webhook: vacancy extended', array_merge(
                $logContext,
                [
                    'vacancy_id'     => $vacancy->id,
                    'days'           => $days,
                    'new_expires_at' => $vacancy->expires_at?->toIso8601String(),
                ],
            ));
        });
    }

    /**
     * Create a Stripe Checkout Session for vacancy extension (days-based).
     * TODO: буде замінено на CheckoutService з модуля 11A (PaymentGateway abstraction).
     */
    public function createVacancyExtensionCheckout(Vacancy $vacancy, int $days): string
    {
        $prices = [15 => 10000, 30 => 20000, 90 => 50000]; // UAH копійки
        $amount = $prices[$days] ?? 20000;

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'uah',
                    'product_data' => [
                        'name'        => "Продовження публікації на {$days} днів",
                        'description' => $vacancy->title,
                    ],
                    'unit_amount'  => $amount,
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('payment.cancel'),
            'metadata'    => [
                'type'       => 'vacancy_extension',
                'vacancy_id' => (string) $vacancy->id,
                'days'       => (string) $days,
            ],
        ]);

        return $session->url;
    }

    private function handleVacancyFeatured(Session $session): void
    {
        $vacancyId = (int) ($session->metadata->vacancy_id ?? 0);

        if ($vacancyId <= 0) {
            Log::channel('payments')->warning('Stripe webhook: vacancy_featured missing vacancy_id', [
                'session_id' => $session->id,
            ]);
            return;
        }

        Vacancy::where('id', $vacancyId)->update([
            'is_featured'    => true,
            'featured_until' => now()->addDays(self::FEATURED_DAYS),
        ]);

        Log::channel('payments')->info('Stripe webhook: vacancy featured', [
            'session_id' => $session->id,
            'vacancy_id' => $vacancyId,
        ]);
    }
}
