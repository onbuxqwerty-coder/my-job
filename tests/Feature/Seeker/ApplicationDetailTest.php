<?php

declare(strict_types=1);

namespace Tests\Feature\Seeker;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewStatus;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $candidate;
    protected User $employer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->candidate = User::factory()->create();
        $this->employer  = User::factory()->employer()->create();
    }

    #[Test]
    public function candidate_can_view_own_application_detail(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);

        $this->actingAs($this->candidate)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(200);
    }

    #[Test]
    public function candidate_cannot_view_another_users_application(): void
    {
        $other       = User::factory()->create();
        $application = Application::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->candidate)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $application = Application::factory()->create();

        $this->get("/dashboard/seeker/applications/{$application->id}")
            ->assertRedirect('/login');
    }

    #[Test]
    public function employer_cannot_access_seeker_application_detail(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);

        $this->actingAs($this->employer)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function application_detail_shows_vacancy_title(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);
        $application->load('vacancy');

        $this->actingAs($this->candidate)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(200)
            ->assertSee($application->vacancy->title);
    }

    #[Test]
    public function application_detail_shows_upcoming_interview_section(): void
    {
        $application = Application::factory()->create([
            'user_id' => $this->candidate->id,
            'status'  => ApplicationStatus::Interview,
        ]);

        Interview::factory()->upcoming()->create([
            'application_id' => $application->id,
            'status'         => InterviewStatus::Scheduled,
        ]);

        $this->actingAs($this->candidate)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(200)
            ->assertSee('Запланована співбесіда');
    }

    #[Test]
    public function application_detail_shows_progress_bar_labels(): void
    {
        $application = Application::factory()->create([
            'user_id' => $this->candidate->id,
            'status'  => ApplicationStatus::Screening,
        ]);

        $this->actingAs($this->candidate)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(200)
            ->assertSee('Розгляд');
    }

    #[Test]
    public function application_detail_shows_rejection_message_when_rejected(): void
    {
        $application = Application::factory()->create([
            'user_id' => $this->candidate->id,
            'status'  => ApplicationStatus::Rejected,
        ]);

        $this->actingAs($this->candidate)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(200)
            ->assertSee('відхилена');
    }

    #[Test]
    public function application_not_found_returns_403(): void
    {
        $this->actingAs($this->candidate)
            ->get('/dashboard/seeker/applications/99999')
            ->assertStatus(403);
    }

    #[Test]
    public function application_detail_shows_back_link_to_applications(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);

        $this->actingAs($this->candidate)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(200)
            ->assertSee('Назад до заявок');
    }

    #[Test]
    public function application_detail_shows_vacancy_description_section(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);

        $this->actingAs($this->candidate)
            ->get("/dashboard/seeker/applications/{$application->id}")
            ->assertStatus(200)
            ->assertSee('Опис вакансії');
    }
}
