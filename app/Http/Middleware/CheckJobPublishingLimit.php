<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckJobPublishingLimit
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->subscriptionService->canPublishJob(auth()->user())) {
            return response()->json(
                ['message' => 'Ліміт вакансій вичерпано. Оновіть тариф.'],
                403,
            );
        }

        return $next($request);
    }
}
