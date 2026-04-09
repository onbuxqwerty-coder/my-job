<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;

final class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $this->paymentService->handleWebhook($payload, $signature);
        } catch (SignatureVerificationException) {
            return response('Invalid signature.', 400);
        } catch (\UnexpectedValueException) {
            return response('Invalid payload.', 400);
        } catch (\Throwable $e) {
            logger()->error('Stripe webhook error: ' . $e->getMessage());
            return response('Webhook error.', 500);
        }

        return response('OK', 200);
    }
}
