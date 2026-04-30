<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanFeature;
use App\Enums\PlanType;
use App\Models\EmployerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vacancy;

final class SubscriptionService
{
    /**
     * Activate a plan for the employer, cancelling any previous active subscription.
     */
    public function activate(User $employer, SubscriptionPlan $plan, int $months = 1): EmployerSubscription
    {
        EmployerSubscription::where('user_id', $employer->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $endsAt = $plan->type === PlanType::Free
            ? now()->addYears(100)
            : now()->addMonths($months);

        return EmployerSubscription::create([
            'user_id'   => $employer->id,
            'plan_id'   => $plan->id,
            'status'    => 'active',
            'starts_at' => now(),
            'ends_at'   => $endsAt,
        ]);
    }

    /**
     * Cancel an active subscription.
     */
    public function cancel(EmployerSubscription $subscription): void
    {
        $subscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Check whether the employer is within the feature limit.
     * Returns true if unlimited (0) or limit not reached.
     */
    public function checkLimit(User $employer, PlanFeature $feature): bool
    {
        $limit = $employer->hasFeature($feature);

        if ($limit === false) {
            return false;
        }

        if (is_int($limit) && $limit === 0) {
            return true;
        }

        return match ($feature) {
            PlanFeature::ActiveJobs => $this->activeJobsCount($employer) < (int) $limit,
            default                 => (bool) $limit,
        };
    }

    /**
     * Count current active vacancies for the employer.
     */
    public function activeJobsCount(User $employer): int
    {
        $companyId = $employer->company?->id;

        if (! $companyId) {
            return 0;
        }

        return Vacancy::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Check if the employer can publish a new vacancy.
     */
    public function canPublishJob(User $employer): bool
    {
        return $this->checkLimit($employer, PlanFeature::ActiveJobs);
    }

    /**
     * How many HOT promotions remain this month.
     */
    public function getRemainingHot(User $employer): int
    {
        $limit = (int) ($employer->hasFeature(PlanFeature::HotPerMonth) ?: 0);

        if ($limit === 0) {
            return 0;
        }

        $companyId = $employer->company?->id;

        $usedThisMonth = $companyId
            ? Vacancy::where('company_id', $companyId)
                ->where('is_hot', true)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count()
            : 0;

        return max(0, $limit - $usedThisMonth);
    }
}
