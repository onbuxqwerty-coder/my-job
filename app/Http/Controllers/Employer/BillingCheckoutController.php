<?php

declare(strict_types=1);

namespace App\Http\Controllers\Employer;

use App\Enums\PlanType;
use App\Models\SubscriptionPlan;
use App\Payments\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BillingCheckoutController
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    public function __invoke(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        if ($plan->type === PlanType::Free || $plan->price_monthly <= 0) {
            return redirect()->route('employer.billing')
                ->with('error', 'Безкоштовний план активується напряму.');
        }

        $url = $this->checkout->createPlanSubscriptionCheckout($request->user(), $plan);

        return redirect($url);
    }
}
