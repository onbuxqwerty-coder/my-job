<?php

declare(strict_types=1);

namespace Tests\Feature\Employer;

use App\Enums\UserRole;
use App\Enums\VacancyPublicationType;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnonymousVacancyTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;
    private Company $company;
    private Vacancy $vacancy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employer = User::factory()->create(['role' => UserRole::Employer]);
        $this->company  = Company::factory()->create([
            'user_id' => $this->employer->id,
            'name'    => 'ТОВ "Тестова Компанія"',
        ]);
        $this->vacancy  = Vacancy::factory()->create([
            'company_id'       => $this->company->id,
            'status'           => VacancyStatus::Active,
            'publication_type' => VacancyPublicationType::Standard,
        ]);
    }

    #[Test]
    public function vacancy_defaults_to_standard_publication_type(): void
    {
        $this->assertEquals(VacancyPublicationType::Standard, $this->vacancy->publication_type);
        $this->assertFalse($this->vacancy->isAnonymous());
    }

    #[Test]
    public function anonymous_vacancy_shows_default_name_when_no_pseudonym(): void
    {
        $this->vacancy->update([
            'publication_type' => VacancyPublicationType::Anonymous,
            'anonymous_name'   => null,
        ]);

        $this->assertEquals('Компанія', $this->vacancy->fresh()->display_company_name);
    }

    #[Test]
    public function anonymous_vacancy_shows_custom_pseudonym(): void
    {
        $this->vacancy->update([
            'publication_type' => VacancyPublicationType::Anonymous,
            'anonymous_name'   => 'Великий Бізнес',
        ]);

        $this->assertEquals('Великий Бізнес', $this->vacancy->fresh()->display_company_name);
    }

    #[Test]
    public function standard_vacancy_shows_real_company_name(): void
    {
        $this->assertEquals(
            $this->company->name,
            $this->vacancy->display_company_name
        );
    }

    #[Test]
    public function anonymous_vacancy_excluded_from_company_public_profile(): void
    {
        $this->vacancy->update(['publication_type' => VacancyPublicationType::Anonymous]);

        $publicVacancies = Vacancy::where('company_id', $this->company->id)
            ->where('status', VacancyStatus::Active)
            ->where('publication_type', VacancyPublicationType::Standard)
            ->get();

        $this->assertCount(0, $publicVacancies);
    }

    #[Test]
    public function auto_refresh_command_updates_published_at(): void
    {
        $oldDate = now()->subDays(7);

        $this->vacancy->update([
            'publication_type'   => VacancyPublicationType::Anonymous,
            'auto_refresh'       => true,
            'auto_refresh_until' => now()->addDays(30),
            'published_at'       => $oldDate,
        ]);

        $this->artisan('vacancies:refresh-anonymous')->assertSuccessful();

        $this->assertGreaterThan(
            $oldDate,
            $this->vacancy->fresh()->published_at
        );
    }

    #[Test]
    public function expired_auto_refresh_does_not_update_vacancy(): void
    {
        $oldDate = now()->subDays(7);

        $this->vacancy->update([
            'publication_type'   => VacancyPublicationType::Anonymous,
            'auto_refresh'       => true,
            'auto_refresh_until' => now()->subDay(),
            'published_at'       => $oldDate,
        ]);

        $this->artisan('vacancies:refresh-anonymous')->assertSuccessful();

        $this->assertEquals(
            $oldDate->toDateTimeString(),
            $this->vacancy->fresh()->published_at->toDateTimeString()
        );
    }
}
