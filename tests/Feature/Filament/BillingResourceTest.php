<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PlanType;
use App\Enums\UserRole;
use App\Filament\Resources\EmployerSubscriptions\Pages\ListEmployerSubscriptions;
use App\Filament\Resources\EmployerSubscriptions\Pages\ViewEmployerSubscription;
use App\Filament\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan;
use App\Filament\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use App\Filament\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use App\Models\EmployerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function makePlan(array $overrides = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'type'          => PlanType::Start,
            'name'          => 'Старт',
            'price_monthly' => 799,
            'is_active'     => true,
            'features'      => [
                'active_jobs'            => 3,
                'applications_per_month' => 50,
                'analytics'              => false,
                'message_templates'      => false,
                'hot_per_month'          => 0,
                'top_per_month'          => 0,
                'hot_days'               => 7,
                'top_days'               => 7,
                'api_access'             => false,
                'team_members'           => 1,
            ],
        ], $overrides));
    }

    private function makeActiveSubscription(SubscriptionPlan $plan): EmployerSubscription
    {
        $employer = User::factory()->create(['role' => UserRole::Employer]);

        return EmployerSubscription::create([
            'user_id'   => $employer->id,
            'plan_id'   => $plan->id,
            'status'    => 'active',
            'starts_at' => now(),
            'ends_at'   => now()->addMonth(),
        ]);
    }

    #[Test]
    public function admin_can_view_subscription_plans_list(): void
    {
        $plan = $this->makePlan();

        Livewire::test(ListSubscriptionPlans::class)
            ->assertCanSeeTableRecords([$plan]);
    }

    #[Test]
    public function admin_can_create_subscription_plan(): void
    {
        Livewire::test(CreateSubscriptionPlan::class)
            ->fillForm([
                'name'          => 'Новий тариф',
                'type'          => PlanType::Business->value,
                'price_monthly' => 1990,
                'is_active'     => true,
                'features'      => [
                    'active_jobs'            => 10,
                    'applications_per_month' => 0,
                    'analytics'              => true,
                    'message_templates'      => true,
                    'hot_per_month'          => 1,
                    'top_per_month'          => 0,
                    'hot_days'               => 30,
                    'top_days'               => 30,
                    'api_access'             => false,
                    'team_members'           => 3,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('subscription_plans', [
            'name'          => 'Новий тариф',
            'price_monthly' => 1990,
        ]);
    }

    #[Test]
    public function admin_can_edit_subscription_plan(): void
    {
        $plan = $this->makePlan();

        Livewire::test(EditSubscriptionPlan::class, ['record' => $plan->getRouteKey()])
            ->fillForm(['name' => 'Оновлений тариф', 'price_monthly' => 999])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('subscription_plans', [
            'id'            => $plan->id,
            'name'          => 'Оновлений тариф',
            'price_monthly' => 999,
        ]);
    }

    #[Test]
    public function admin_cannot_delete_plan_with_active_subscriptions(): void
    {
        $plan = $this->makePlan();
        $this->makeActiveSubscription($plan);

        Livewire::test(ListSubscriptionPlans::class)
            ->callTableAction('delete', $plan);

        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }

    #[Test]
    public function admin_can_view_employer_subscriptions_list(): void
    {
        $plan         = $this->makePlan();
        $subscription = $this->makeActiveSubscription($plan);

        Livewire::test(ListEmployerSubscriptions::class)
            ->assertCanSeeTableRecords([$subscription]);
    }

    #[Test]
    public function admin_can_cancel_employer_subscription(): void
    {
        $plan         = $this->makePlan();
        $subscription = $this->makeActiveSubscription($plan);

        Livewire::test(ViewEmployerSubscription::class, ['record' => $subscription->getRouteKey()])
            ->callAction('cancel');

        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertNotNull($subscription->fresh()->cancelled_at);
    }
}
