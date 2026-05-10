<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Enums\VacancyStatus;
use App\Models\City;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JobPostingSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function makeActiveVacancy(array $overrides = []): Vacancy
    {
        $employer = User::factory()->employer()->create();
        $company  = Company::factory()->create(['user_id' => $employer->id]);

        return Vacancy::factory()->active()->create(array_merge([
            'company_id'      => $company->id,
            'employment_type' => ['full-time'],
        ], $overrides));
    }

    #[Test]
    public function vacancy_page_contains_job_posting_schema(): void
    {
        $vacancy = $this->makeActiveVacancy();

        $this->get("/jobs/{$vacancy->slug}")
            ->assertOk()
            ->assertSee('application/ld+json', escape: false)
            ->assertSee('"@type": "JobPosting"', escape: false);
    }

    #[Test]
    public function schema_includes_salary_when_present(): void
    {
        $vacancy = $this->makeActiveVacancy([
            'salary_from' => 50000,
            'salary_to'   => 80000,
            'currency'    => 'UAH',
        ]);

        $this->get("/jobs/{$vacancy->slug}")
            ->assertOk()
            ->assertSee('baseSalary', escape: false)
            ->assertSee('50000', escape: false)
            ->assertSee('80000', escape: false);
    }

    #[Test]
    public function schema_omits_salary_when_absent(): void
    {
        $vacancy = $this->makeActiveVacancy([
            'salary_from' => null,
            'salary_to'   => null,
        ]);

        $this->get("/jobs/{$vacancy->slug}")
            ->assertOk()
            ->assertDontSee('baseSalary', escape: false);
    }

    #[Test]
    public function remote_vacancy_includes_telecommute_field(): void
    {
        $vacancy = $this->makeActiveVacancy([
            'employment_type' => ['remote'],
        ]);

        $this->get("/jobs/{$vacancy->slug}")
            ->assertOk()
            ->assertSee('TELECOMMUTE', escape: false)
            ->assertSee('jobLocationType', escape: false);
    }

    #[Test]
    public function schema_strips_html_from_description(): void
    {
        $vacancy = $this->makeActiveVacancy([
            'description' => '<p>Ми шукаємо <strong>PHP developer</strong></p>',
        ]);

        $response = $this->get("/jobs/{$vacancy->slug}")->assertOk();

        $this->assertStringContainsString('Ми шукаємо PHP developer', $response->getContent());
        $this->assertStringNotContainsString('<p>Ми шукаємо', $response->getContent());
    }
}
