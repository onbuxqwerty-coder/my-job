<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Policies\VacancyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacancyPolicyTest extends TestCase
{
    use RefreshDatabase;

    private VacancyPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new VacancyPolicy();
    }

    private function makeOwner(): array
    {
        $user    = User::factory()->employer()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);
        $vacancy = Vacancy::factory()->active()->create(['company_id' => $company->id]);
        return [$user, $vacancy];
    }

    public function test_extend_allowed_for_owner_with_active_vacancy(): void
    {
        [$user, $vacancy] = $this->makeOwner();

        $this->assertTrue($this->policy->extend($user, $vacancy));
    }

    public function test_extend_denied_for_other_user(): void
    {
        [, $vacancy] = $this->makeOwner();
        $other = User::factory()->employer()->create();

        $this->assertFalse($this->policy->extend($other, $vacancy));
    }

    public function test_extend_denied_for_archived_vacancy(): void
    {
        $user    = User::factory()->employer()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);
        $vacancy = Vacancy::factory()->archived()->create(['company_id' => $company->id]);

        $this->assertFalse($this->policy->extend($user, $vacancy));
    }

    public function test_extend_allowed_for_expired_vacancy_owner(): void
    {
        $user    = User::factory()->employer()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);
        $vacancy = Vacancy::factory()->expired()->create(['company_id' => $company->id]);

        $this->assertTrue($this->policy->extend($user, $vacancy));
    }

    public function test_view_allowed_for_owner(): void
    {
        [$user, $vacancy] = $this->makeOwner();

        $this->assertTrue($this->policy->view($user, $vacancy));
    }

    public function test_view_denied_for_other_user(): void
    {
        [, $vacancy] = $this->makeOwner();
        $other = User::factory()->employer()->create();

        $this->assertFalse($this->policy->view($other, $vacancy));
    }
}
