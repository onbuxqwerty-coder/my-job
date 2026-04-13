<?php

declare(strict_types=1);

namespace Tests\Feature\Seeker;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InterviewsTest extends TestCase
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
    public function interviews_page_loads_successfully(): void
    {
        $this->actingAs($this->candidate)
            ->get('/dashboard/seeker/interviews')
            ->assertStatus(200);
    }

    #[Test]
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/dashboard/seeker/interviews')
            ->assertRedirect('/login');
    }

    #[Test]
    public function employer_cannot_access_interviews_page(): void
    {
        $this->actingAs($this->employer)
            ->get('/dashboard/seeker/interviews')
            ->assertStatus(403);
    }

    #[Test]
    public function upcoming_computed_returns_only_future_scheduled_interviews(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);

        Interview::factory()->upcoming()->create(['application_id' => $application->id]);
        Interview::factory()->past()->create(['application_id' => $application->id]);

        $this->actingAs($this->candidate);

        $component = Volt::test('pages.seeker.interviews');
        $upcoming  = $component->instance()->upcoming();

        $this->assertEquals(1, $upcoming->count());
    }

    #[Test]
    public function past_computed_returns_past_and_cancelled_interviews(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);

        Interview::factory()->past()->create(['application_id' => $application->id]);
        Interview::factory()->cancelled()->create(['application_id' => $application->id]);
        Interview::factory()->upcoming()->create(['application_id' => $application->id]);

        $this->actingAs($this->candidate);

        $component = Volt::test('pages.seeker.interviews');
        $past      = $component->instance()->past();

        $this->assertEquals(2, $past->count());
    }

    #[Test]
    public function interviews_only_show_own_candidate_interviews(): void
    {
        $other            = User::factory()->create();
        $ownApplication   = Application::factory()->create(['user_id' => $this->candidate->id]);
        $otherApplication = Application::factory()->create(['user_id' => $other->id]);

        Interview::factory()->upcoming()->create(['application_id' => $ownApplication->id]);
        Interview::factory()->upcoming()->create(['application_id' => $otherApplication->id]);

        $this->actingAs($this->candidate);

        $component = Volt::test('pages.seeker.interviews');
        $upcoming  = $component->instance()->upcoming();

        $this->assertEquals(1, $upcoming->count());
    }

    #[Test]
    public function video_interview_shows_join_button(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);

        Interview::factory()->upcoming()->video()->create([
            'application_id' => $application->id,
        ]);

        $this->actingAs($this->candidate)
            ->get('/dashboard/seeker/interviews')
            ->assertStatus(200)
            ->assertSee('Приєднатись');
    }

    #[Test]
    public function empty_upcoming_state_shows_message(): void
    {
        $this->actingAs($this->candidate);

        Volt::test('pages.seeker.interviews')
            ->set('tab', 'upcoming')
            ->assertSee('Немає запланованих');
    }

    #[Test]
    public function empty_past_state_shows_message(): void
    {
        $this->actingAs($this->candidate);

        Volt::test('pages.seeker.interviews')
            ->set('tab', 'past')
            ->assertSee('Минулих');
    }

    #[Test]
    public function interview_type_phone_label_is_rendered(): void
    {
        $application = Application::factory()->create(['user_id' => $this->candidate->id]);

        Interview::factory()->upcoming()->create([
            'application_id' => $application->id,
            'type'           => InterviewType::Phone,
        ]);

        $this->actingAs($this->candidate)
            ->get('/dashboard/seeker/interviews')
            ->assertStatus(200)
            ->assertSee('Телефонна');
    }

    #[Test]
    public function default_tab_is_upcoming(): void
    {
        $this->actingAs($this->candidate);

        Volt::test('pages.seeker.interviews')
            ->assertSet('tab', 'upcoming');
    }

    #[Test]
    public function tab_can_be_switched_to_past(): void
    {
        $this->actingAs($this->candidate);

        Volt::test('pages.seeker.interviews')
            ->set('tab', 'past')
            ->assertSet('tab', 'past');
    }
}
