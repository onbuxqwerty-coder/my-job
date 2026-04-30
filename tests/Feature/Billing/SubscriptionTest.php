<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\PlanFeature;
use App\Enums\PlanType;
use App\Enums\UserRole;
use App\Models\EmployerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(string $type, array $featureOverrides = []): SubscriptionPlan
    {
        $defaults = [
            'free'     => ['active_jobs' => 1,  'applications_per_month' => 10,  'analytics' => false, 'message_templates' => false, 'hot_per_month' => 0, 'top_per_month' => 0, 'api_access' => false, 'team_members' => 1],
            'start'    => ['active_jobs' => 3,  'applications_per_month' => 50,  'analytics' => false, 'message_templates' => false, 'hot_per_month' => 0, 'top_per_month' => 0, 'api_access' => false, 'team_members' => 1],
            'business' => ['active_jobs' => 10, 'applications_per_month' => 0,   'analytics' => true,  'message_templates' => true,  'hot_per_month' => 1, 'top_per_month' => 0, 'api_access' => false, 'team_members' => 3],
            'pro'      => ['active_jobs' => 0,  'applications_per_month' => 0,   'analytics' => true,  'message_templates' => true,  'hot_per_month' => 3, 'top_per_month' => 1, 'api_access' => true,  'team_members' => 0],
        ];

        return SubscriptionPlan::create([
            'type'          => $type,
            'name'          => ucfirst($type),
            'price_monthly' => match($type) { 'free' => 0, 'start' => 799, 'business' => 1990, default => 4490 },
            'features'      => array_merge($defaults[$type] ?? [], $featureOverrides),
        ]);
    }

    private function makeEmployer(): User
    {
        return User::factory()->create(['role' => UserRole::Employer]);
    }

    #[Test]
    public function employer_starts_with_free_plan_by_default(): void
    {
        $employer = $this->makeEmployer();

        $this->assertNull($employer->currentPlan());
        $this->assertFalse($employer->hasFeature(PlanFeature::Analytics));
    }

    #[Test]
    public function can_activate_paid_subscription(): void
    {
        $employer = $this->makeEmployer();
        $plan     = $this->makePlan('start');

        $subscription = app(SubscriptionService::class)->activate($employer, $plan);

        $this->assertSame('active', $subscription->status);
        $this->assertSame($plan->id, $employer->fresh()->currentPlan()?->id);
    }

    #[Test]
    public function activation_cancels_previous_subscription(): void
    {
        $employer = $this->makeEmployer();
        $free     = $this->makePlan('free');
        $start    = $this->makePlan('start');

        $service = app(SubscriptionService::class);
        $service->activate($employer, $free);
        $service->activate($employer, $start);

        $cancelled = EmployerSubscription::where('user_id', $employer->id)
            ->where('status', 'cancelled')
            ->count();

        $this->assertSame(1, $cancelled);
        $this->assertSame(PlanType::Start, $employer->fresh()->currentPlan()?->type);
    }

    #[Test]
    public function expired_subscription_blocks_job_publishing(): void
    {
        $employer = $this->makeEmployer();
        $plan     = $this->makePlan('start');

        EmployerSubscription::create([
            'user_id'   => $employer->id,
            'plan_id'   => $plan->id,
            'status'    => 'active',
            'starts_at' => now()->subMonths(2),
            'ends_at'   => now()->subDay(),
        ]);

        $this->assertFalse(app(SubscriptionService::class)->canPublishJob($employer));
    }

    #[Test]
    public function start_plan_limits_active_jobs_to_three(): void
    {
        $employer = $this->makeEmployer();
        $plan     = $this->makePlan('start');

        app(SubscriptionService::class)->activate($employer, $plan);

        $this->assertSame(3, $plan->feature(PlanFeature::ActiveJobs));
        $this->assertTrue(app(SubscriptionService::class)->canPublishJob($employer));
    }

    #[Test]
    public function business_plan_allows_unlimited_applications(): void
    {
        $employer = $this->makeEmployer();
        $plan     = $this->makePlan('business');

        app(SubscriptionService::class)->activate($employer, $plan);

        $this->assertSame(0, $plan->feature(PlanFeature::ApplicationsPerMonth));
        $this->assertTrue(app(SubscriptionService::class)->checkLimit($employer, PlanFeature::ApplicationsPerMonth));
    }

    #[Test]
    public function pro_plan_has_unlimited_active_jobs(): void
    {
        $employer = $this->makeEmployer();
        $plan     = $this->makePlan('pro');

        app(SubscriptionService::class)->activate($employer, $plan);

        $this->assertSame(0, $plan->feature(PlanFeature::ActiveJobs));
        $this->assertTrue(app(SubscriptionService::class)->canPublishJob($employer));
    }

    #[Test]
    public function can_get_remaining_hot_promotions(): void
    {
        $employer = $this->makeEmployer();
        $plan     = $this->makePlan('business');

        app(SubscriptionService::class)->activate($employer, $plan);

        $remaining = app(SubscriptionService::class)->getRemainingHot($employer);

        $this->assertSame(1, $remaining);
    }
}
