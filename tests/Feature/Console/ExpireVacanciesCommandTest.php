<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireVacanciesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_transitions_overdue_active_vacancies_to_expired(): void
    {
        $expiredOne   = Vacancy::factory()->active()->create(['expires_at' => now()->subHours(2)]);
        $expiredTwo   = Vacancy::factory()->active()->create(['expires_at' => now()->subDays(1)]);
        $stillActive  = Vacancy::factory()->active(daysLeft: 5)->create();
        $alreadyExpired = Vacancy::factory()->expired()->create();

        $this->artisan('vacancies:expire')
            ->expectsOutputToContain('Завершено 2')
            ->assertSuccessful();

        $this->assertSame(VacancyStatus::Expired, $expiredOne->fresh()->status);
        $this->assertSame(VacancyStatus::Expired, $expiredTwo->fresh()->status);
        $this->assertSame(VacancyStatus::Active,  $stillActive->fresh()->status);
        $this->assertSame(VacancyStatus::Expired, $alreadyExpired->fresh()->status);
    }

    public function test_dry_run_does_not_change_database(): void
    {
        $v = Vacancy::factory()->active()->create(['expires_at' => now()->subHour()]);

        $this->artisan('vacancies:expire', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(VacancyStatus::Active, $v->fresh()->status);
    }

    public function test_command_reports_zero_when_nothing_to_expire(): void
    {
        Vacancy::factory()->active(daysLeft: 5)->count(3)->create();

        $this->artisan('vacancies:expire')
            ->expectsOutputToContain('Завершено 0')
            ->assertSuccessful();
    }

    public function test_command_processes_large_batches_via_chunk(): void
    {
        // Shared category+company — CategoryFactory has only 10 unique names,
        // creating 150 separate categories would overflow Faker's unique generator
        $company  = \App\Models\Company::factory()->create();
        $category = \App\Models\Category::factory()->create();

        Vacancy::factory()->active()->count(150)->create([
            'expires_at'  => now()->subHour(),
            'company_id'  => $company->id,
            'category_id' => $category->id,
        ]);

        $this->artisan('vacancies:expire', ['--batch' => 50])
            ->expectsOutputToContain('Завершено 150')
            ->assertSuccessful();

        $this->assertSame(150, Vacancy::expired()->count());
    }
}
