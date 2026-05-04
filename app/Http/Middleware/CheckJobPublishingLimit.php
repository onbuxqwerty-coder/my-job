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
            return redirect()
                ->route('employer.dashboard')
                ->with('limit_exceeded', true);
        }

        return $next($request);
    }
}
