<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\VacancyStatus;
use App\Livewire\Employer\PaymentStatusPoller;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentStatusPollerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-06-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Active vacancy with expires_at = null so isConfirmed starts as false.
     */
    private function makeVacancy(): Vacancy
    {
        $user    = User::factory()->employer()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return Vacancy::factory()->create([
            'company_id'   => $company->id,
            'status'       => VacancyStatus::Active,
            'is_active'    => true,
            'published_at' => now()->subDays(5),
            'expires_at'   => null,
        ]);
    }

    public function test_renders_pending_state_initially(): void
    {
        $vacancy = $this->makeVacancy();

        Livewire::test(PaymentStatusPoller::class, ['vacancy' => $vacancy])
            ->assertSee('Очікуємо підтвердження від провайдера');
    }

    public function test_pending_state_when_expires_at_is_null(): void
    {
        $vacancy = $this->makeVacancy();

        Livewire::test(PaymentStatusPoller::class, ['vacancy' => $vacancy])
            ->assertDontSee('Вакансію продовжено')
            ->assertDontSee('Обробка займає більше часу');
    }

    public function test_confirmed_state_shows_success_message(): void
    {
        $vacancy   = $this->makeVacancy();
        $startedAt = now()->subSeconds(5)->toIso8601String();

        $vacancy->update(['expires_at' => now()->addDays(30)]);
        $vacancy->refresh();

        Livewire::test(PaymentStatusPoller::class, ['vacancy' => $vacancy])
            ->set('startedAt', $startedAt)
            ->assertSee('Вакансію продовжено');
    }

    public function test_confirmed_hidden_when_expires_at_before_started_at(): void
    {
        $vacancy = $this->makeVacancy();

        // expires_at is in the past relative to the mount startedAt (now())
        $vacancy->update(['expires_at' => now()->subSeconds(5)]);
        $vacancy->refresh();

        Livewire::test(PaymentStatusPoller::class, ['vacancy' => $vacancy])
            ->assertDontSee('Вакансію продовжено');
    }

    public function test_timeout_state_shown_when_wait_exceeds_limit(): void
    {
        $vacancy = $this->makeVacancy();

        // startedAt far in the past, tiny maxWaitSeconds → triggers timeout
        Livewire::test(PaymentStatusPoller::class, ['vacancy' => $vacancy])
            ->set('startedAt', now()->subSeconds(60)->toIso8601String())
            ->set('maxWaitSeconds', 10)
            ->assertSee('Обробка займає більше часу');
    }

    public function test_pending_shown_when_wait_within_limit(): void
    {
        $vacancy = $this->makeVacancy();

        Livewire::test(PaymentStatusPoller::class, ['vacancy' => $vacancy])
            ->set('startedAt', now()->subSeconds(5)->toIso8601String())
            ->set('maxWaitSeconds', 300)
            ->assertSee('Очікуємо підтвердження від провайдера');
    }

    public function test_timeout_not_shown_when_within_limit(): void
    {
        $vacancy = $this->makeVacancy();

        Livewire::test(PaymentStatusPoller::class, ['vacancy' => $vacancy])
            ->set('startedAt', now()->subSeconds(5)->toIso8601String())
            ->set('maxWaitSeconds', 300)
            ->assertDontSee('Обробка займає більше часу');
    }

    public function test_refresh_method_reloads_vacancy(): void
    {
        $vacancy = $this->makeVacancy();

        $component = Livewire::test(PaymentStatusPoller::class, ['vacancy' => $vacancy]);

        $vacancy->update(['expires_at' => now()->addDays(30)]);

        $component->call('refresh');

        $this->assertNotNull($component->instance()->vacancy->expires_at);
    }
}
