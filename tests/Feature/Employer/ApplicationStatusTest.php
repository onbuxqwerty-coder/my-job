<?php

declare(strict_types=1);

namespace Tests\Feature\Employer;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployerWithApplication(ApplicationStatus $status = ApplicationStatus::Pending): array
    {
        $employer    = User::factory()->employer()->create();
        $company     = Company::factory()->create(['user_id' => $employer->id]);
        $vacancy     = Vacancy::factory()->create(['company_id' => $company->id]);
        $candidate   = User::factory()->create(['role' => UserRole::Candidate]);
        $application = Application::factory()->create([
            'vacancy_id' => $vacancy->id,
            'user_id'    => $candidate->id,
            'status'     => $status,
        ]);

        return [$employer, $application, $candidate];
    }

    #[Test]
    public function employer_can_change_application_status(): void
    {
        [$employer, $application] = $this->makeEmployerWithApplication(ApplicationStatus::Viewed);

        $this->actingAs($employer);

        Volt::test('employer.application-status-form', ['applicationId' => $application->id])
            ->set('newStatus', ApplicationStatus::Reviewing->value)
            ->set('comment', 'Переходимо до розгляду')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(ApplicationStatus::Reviewing, $application->fresh()->status);
    }

    #[Test]
    public function status_change_is_logged(): void
    {
        [$employer, $application] = $this->makeEmployerWithApplication(ApplicationStatus::Viewed);

        $this->actingAs($employer);

        Volt::test('employer.application-status-form', ['applicationId' => $application->id])
            ->set('newStatus', ApplicationStatus::Interview->value)
            ->set('comment', 'Запрошуємо на співбесіду')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('application_status_logs', [
            'application_id' => $application->id,
            'status'         => ApplicationStatus::Interview->value,
            'comment'        => 'Запрошуємо на співбесіду',
        ]);
    }

    #[Test]
    public function application_auto_transitions_to_viewed(): void
    {
        [$employer, $application] = $this->makeEmployerWithApplication(ApplicationStatus::Pending);

        $this->assertEquals(ApplicationStatus::Pending, $application->status);

        $this->actingAs($employer);

        Volt::test('employer.application-status-form', ['applicationId' => $application->id]);

        $this->assertEquals(ApplicationStatus::Viewed, $application->fresh()->status);

        $this->assertDatabaseHas('application_status_logs', [
            'application_id' => $application->id,
            'status'         => ApplicationStatus::Viewed->value,
            'changed_by'     => null,
        ]);
    }

    #[Test]
    public function candidate_can_withdraw_application(): void
    {
        [$employer, $application, $candidate] = $this->makeEmployerWithApplication(ApplicationStatus::Pending);

        $this->actingAs($candidate);

        Volt::test('candidate.application-timeline', ['applicationId' => $application->id])
            ->call('withdraw')
            ->assertHasNoErrors();

        $this->assertEquals(ApplicationStatus::Withdrawn, $application->fresh()->status);
    }

    #[Test]
    public function candidate_cannot_withdraw_after_interview(): void
    {
        [$employer, $application, $candidate] = $this->makeEmployerWithApplication(ApplicationStatus::Interview);

        $this->actingAs($candidate);

        Volt::test('candidate.application-timeline', ['applicationId' => $application->id])
            ->call('withdraw')
            ->assertHasNoErrors();

        $this->assertEquals(ApplicationStatus::Interview, $application->fresh()->status);
    }
}
