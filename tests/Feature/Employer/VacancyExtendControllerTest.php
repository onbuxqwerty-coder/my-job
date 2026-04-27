<?php

declare(strict_types=1);

namespace Tests\Feature\Employer;

use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacancyExtendControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.prices' => [15 => 10000, 30 => 20000, 90 => 50000]]);

        $this->instance(PaymentGateway::class, new class implements PaymentGateway {
            public function name(): string { return 'mock'; }
            public function createCheckout(\App\Payments\DTOs\CheckoutData $data): string
            {
                return 'https://mock-payment.test/pay';
            }
            public function parseWebhook(\Illuminate\Http\Request $request): \App\Payments\DTOs\PaymentResult
            {
                return new \App\Payments\DTOs\PaymentResult(
                    orderId: '',
                    eventId: 'mock_evt',
                    success: true,
                    gatewayName: 'mock',
                    raw: [],
                );
            }
            public function successResponse(): \Illuminate\Http\Response
            {
                return response('ok');
            }
        });
    }

    private function makeEmployer(): array
    {
        $user    = User::factory()->employer()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);
        $vacancy = Vacancy::factory()->active()->create(['company_id' => $company->id]);
        return [$user, $company, $vacancy];
    }

    public function test_guest_redirected_to_login(): void
    {
        $vacancy = Vacancy::factory()->active()->create();

        $this->get(route('employer.vacancies.extend', $vacancy))
            ->assertRedirect(route('login'));
    }

    public function test_employer_can_see_extend_page(): void
    {
        [$user, , $vacancy] = $this->makeEmployer();

        $this->actingAs($user)
            ->get(route('employer.vacancies.extend', $vacancy))
            ->assertOk();
    }

    public function test_other_employer_gets_403_on_extend_page(): void
    {
        [, , $vacancy] = $this->makeEmployer();
        $other = User::factory()->employer()->create();

        $this->actingAs($other)
            ->get(route('employer.vacancies.extend', $vacancy))
            ->assertForbidden();
    }

    public function test_archived_vacancy_returns_403(): void
    {
        $user    = User::factory()->employer()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);
        $vacancy = Vacancy::factory()->archived()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->get(route('employer.vacancies.extend', $vacancy))
            ->assertForbidden();
    }

    public function test_post_initiate_redirects_to_gateway(): void
    {
        [$user, , $vacancy] = $this->makeEmployer();

        $this->actingAs($user)
            ->post(route('employer.vacancies.extend.initiate', $vacancy), ['days' => 30])
            ->assertRedirect('https://mock-payment.test/pay');
    }

    public function test_post_initiate_rejects_invalid_days(): void
    {
        [$user, , $vacancy] = $this->makeEmployer();

        $this->actingAs($user)
            ->post(route('employer.vacancies.extend.initiate', $vacancy), ['days' => 7])
            ->assertSessionHasErrors('days');
    }

    public function test_post_initiate_other_employer_gets_403(): void
    {
        [, , $vacancy] = $this->makeEmployer();
        $other = User::factory()->employer()->create();

        $this->actingAs($other)
            ->post(route('employer.vacancies.extend.initiate', $vacancy), ['days' => 30])
            ->assertForbidden();
    }

    public function test_cancel_redirects_back_to_extend_page(): void
    {
        [$user, , $vacancy] = $this->makeEmployer();

        $this->actingAs($user)
            ->get(route('employer.vacancies.payment.cancel', $vacancy))
            ->assertRedirect(route('employer.vacancies.extend', $vacancy));
    }

    public function test_cancel_sets_warning_session(): void
    {
        [$user, , $vacancy] = $this->makeEmployer();

        $this->actingAs($user)
            ->get(route('employer.vacancies.payment.cancel', $vacancy))
            ->assertSessionHas('warning');
    }

    public function test_success_page_accessible_by_owner(): void
    {
        [$user, , $vacancy] = $this->makeEmployer();

        $this->actingAs($user)
            ->get(route('employer.vacancies.payment.success', $vacancy))
            ->assertOk();
    }

    public function test_success_page_returns_403_for_other_employer(): void
    {
        [, , $vacancy] = $this->makeEmployer();
        $other = User::factory()->employer()->create();

        $this->actingAs($other)
            ->get(route('employer.vacancies.payment.success', $vacancy))
            ->assertForbidden();
    }
}
