<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

final class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        // CRITICAL #1: верифікація підпису
        try {
            $status = $this->paymentService->handleWebhook($payload, $signature);
        } catch (SignatureVerificationException $e) {
            Log::channel('payments')->warning('Stripe webhook: invalid signature', [
                'error' => $e->getMessage(),
                'ip'    => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::channel('payments')->warning('Stripe webhook: invalid payload', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Throwable $e) {
            // CRITICAL #4: повертаємо 200 щоб Stripe не retry-їв фіксовані помилки
            Log::channel('payments')->error('Stripe webhook: handler failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);
            return response()->json(['status' => 'error_logged'], 200);
        }

        return response()->json(['status' => $status], 200);
    }
}
