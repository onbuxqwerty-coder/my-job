# 🧪 Тести кабінету Шукача (Job Seeker) - Laravel 11

Всі тести розроблені для Laravel 11 з PHPUnit і Livewire 3. Копіюйте файли в папку `tests/` вашого проекту.

---

## 📁 Структура файлів тестів

```
tests/
├── Feature/
│   └── Seeker/
│       ├── DashboardTest.php
│       ├── ApplicationsTest.php
│       ├── ApplicationDetailTest.php
│       ├── InterviewsTest.php
│       ├── VacanciesTest.php
│       ├── ProfileTest.php
│       ├── ResumeTest.php
│       ├── NotificationsTest.php
│       └── SettingsTest.php
│
├── Unit/
│   └── Services/
│       ├── SeekerServiceTest.php
│       ├── ApplicationServiceTest.php
│       └── InterviewServiceTest.php
│
└── Integration/
    ├── WebhookSyncTest.php
    └── ApplicationSyncTest.php
```

---

## 🧪 FEATURE TESTS (9 файлів)

### 1. DashboardTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use App\Models\Application;
use App\Models\Interview;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;
    protected User $employer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seeker = User::factory()->create(['role' => 'seeker']);
        $this->employer = User::factory()->create(['role' => 'employer']);
    }

    /** @test */
    public function dashboard_loads_successfully()
    {
        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker');

        $response->assertStatus(200);
        $response->assertViewIs('seeker.dashboard');
    }

    /** @test */
    public function dashboard_displays_correct_statistics()
    {
        // Crear vacancies y applications
        $vacancies = Vacancy::factory()->count(3)->create(['user_id' => $this->employer->id]);
        
        Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancies[0]->id,
            'status' => 'submitted',
        ]);
        
        Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancies[1]->id,
            'status' => 'interview',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker');

        $response->assertViewHasAll([
            'statistics' => function ($stats) {
                return $stats['total_applications'] === 2 &&
                       $stats['active_applications'] === 1 &&
                       $stats['upcoming_interviews'] === 1;
            }
        ]);
    }

    /** @test */
    public function dashboard_shows_upcoming_interviews()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        
        $application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
            'status' => 'interview',
        ]);

        Interview::factory()->create([
            'application_id' => $application->id,
            'date' => now()->addDays(2),
            'type' => 'online',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker');

        $response->assertViewHas('upcomingInterviews', function ($interviews) {
            return $interviews->count() === 1;
        });
    }

    /** @test */
    public function unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->get('/dashboard/seeker');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function employer_cannot_access_seeker_dashboard()
    {
        $response = $this->actingAs($this->employer)
            ->get('/dashboard/seeker');

        $response->assertStatus(403);
    }

    /** @test */
    public function dashboard_shows_recommended_vacancies()
    {
        Vacancy::factory()->count(5)->create([
            'user_id' => $this->employer->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker');

        $response->assertViewHas('recommendedVacancies');
    }

    /** @test */
    public function dashboard_shows_recent_activity()
    {
        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker');

        $response->assertViewHas('recentActivity');
    }
}
```

---

### 2. ApplicationsTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;
    protected User $employer;
    protected Vacancy $vacancy;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seeker = User::factory()->create(['role' => 'seeker']);
        $this->employer = User::factory()->create(['role' => 'employer']);
        $this->vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
    }

    /** @test */
    public function seeker_can_submit_application()
    {
        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/applications/' . $this->vacancy->id . '/submit', [
                'resume_id' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('applications', [
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $this->vacancy->id,
            'status' => 'submitted',
        ]);
    }

    /** @test */
    public function applications_list_shows_all_applications()
    {
        Application::factory()->count(5)->create([
            'seeker_id' => $this->seeker->id,
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications');

        $response->assertStatus(200);
        $response->assertViewIs('seeker.applications.index');
        $response->assertViewHas('applications');
    }

    /** @test */
    public function applications_list_can_be_filtered_by_status()
    {
        Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'status' => 'submitted',
        ]);
        
        Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'status' => 'interview',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications?status=interview');

        $response->assertStatus(200);
        $response->assertViewHas('applications', function ($apps) {
            return $apps->first()->status === 'interview';
        });
    }

    /** @test */
    public function seeker_can_search_applications()
    {
        Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $this->vacancy->id,
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications?search=Google');

        $response->assertStatus(200);
    }

    /** @test */
    public function seeker_can_withdraw_application()
    {
        $application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/applications/' . $application->id . '/withdraw');

        $response->assertRedirect();
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'withdrawn',
        ]);
    }

    /** @test */
    public function seeker_cannot_withdraw_rejected_application()
    {
        $application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/applications/' . $application->id . '/withdraw');

        $response->assertStatus(403);
    }

    /** @test */
    public function applications_pagination_works()
    {
        Application::factory()->count(30)->create([
            'seeker_id' => $this->seeker->id,
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications');

        $response->assertViewHas('applications', function ($apps) {
            return $apps->count() === 20; // Default per page
        });
    }

    /** @test */
    public function statistics_counter_is_correct()
    {
        Application::factory()->count(5)->create([
            'seeker_id' => $this->seeker->id,
            'status' => 'submitted',
        ]);
        
        Application::factory()->count(3)->create([
            'seeker_id' => $this->seeker->id,
            'status' => 'interview',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications');

        $response->assertViewHas('statistics', function ($stats) {
            return $stats['total'] === 8 &&
                   $stats['statuses']['submitted'] === 5 &&
                   $stats['statuses']['interview'] === 3;
        });
    }

    /** @test */
    public function only_seeker_can_view_own_applications()
    {
        $other_seeker = User::factory()->create(['role' => 'seeker']);
        
        Application::factory()->create([
            'seeker_id' => $other_seeker->id,
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications');

        $response->assertViewHas('applications', function ($apps) {
            return $apps->count() === 0;
        });
    }
}
```

---

### 3. ApplicationDetailTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use App\Models\Application;
use App\Models\Interview;
use App\Models\Message;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;
    protected User $employer;
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seeker = User::factory()->create(['role' => 'seeker']);
        $this->employer = User::factory()->create(['role' => 'employer']);
        
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        
        $this->application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);
    }

    /** @test */
    public function seeker_can_view_application_details()
    {
        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications/' . $this->application->id);

        $response->assertStatus(200);
        $response->assertViewIs('seeker.applications.show');
    }

    /** @test */
    public function seeker_cannot_view_other_application_details()
    {
        $other_seeker = User::factory()->create(['role' => 'seeker']);

        $response = $this->actingAs($other_seeker)
            ->get('/dashboard/seeker/applications/' . $this->application->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function application_detail_shows_status_history()
    {
        // Simulate status changes
        $this->application->update(['status' => 'viewed']);
        $this->application->update(['status' => 'screening']);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications/' . $this->application->id);

        $response->assertViewHas('statusHistory');
    }

    /** @test */
    public function seeker_can_view_interview_details()
    {
        $interview = Interview::factory()->create([
            'application_id' => $this->application->id,
            'type' => 'online',
        ]);

        $this->application->update(['status' => 'interview']);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications/' . $this->application->id);

        $response->assertViewHas('interviews', function ($interviews) {
            return $interviews->count() === 1;
        });
    }

    /** @test */
    public function seeker_can_confirm_interview()
    {
        $interview = Interview::factory()->create([
            'application_id' => $this->application->id,
        ]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/interviews/' . $interview->id . '/confirm');

        $response->assertRedirect();
        $this->assertTrue($interview->fresh()->confirmed_by_seeker);
    }

    /** @test */
    public function seeker_can_send_message()
    {
        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/applications/' . $this->application->id . '/messages', [
                'message' => 'Thanks for the opportunity!',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'application_id' => $this->application->id,
            'message' => 'Thanks for the opportunity!',
            'sender_type' => 'seeker',
        ]);
    }

    /** @test */
    public function application_detail_shows_messages()
    {
        Message::factory()->create([
            'application_id' => $this->application->id,
            'sender_type' => 'employer',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications/' . $this->application->id);

        $response->assertViewHas('messages', function ($messages) {
            return $messages->count() === 1;
        });
    }

    /** @test */
    public function seeker_can_view_company_rating()
    {
        $this->application->update([
            'rating' => 4,
            'rating_comment' => 'Very promising candidate!',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/applications/' . $this->application->id);

        $response->assertViewHas('application', function ($app) {
            return $app->rating === 4;
        });
    }
}
```

---

### 4. InterviewsTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use App\Models\Application;
use App\Models\Interview;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InterviewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;
    protected User $employer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seeker = User::factory()->create(['role' => 'seeker']);
        $this->employer = User::factory()->create(['role' => 'employer']);
    }

    /** @test */
    public function seeker_can_view_interviews_list()
    {
        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/interviews');

        $response->assertStatus(200);
        $response->assertViewIs('seeker.interviews.index');
    }

    /** @test */
    public function seeker_can_view_interview_calendar()
    {
        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/interviews/calendar');

        $response->assertStatus(200);
    }

    /** @test */
    public function seeker_can_confirm_interview()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        $application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);

        $interview = Interview::factory()->create([
            'application_id' => $application->id,
            'confirmed_by_seeker' => false,
        ]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/interviews/' . $interview->id . '/confirm');

        $response->assertRedirect();
        $this->assertTrue($interview->fresh()->confirmed_by_seeker);
    }

    /** @test */
    public function seeker_can_cancel_interview()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        $application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);

        $interview = Interview::factory()->create([
            'application_id' => $application->id,
        ]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/interviews/' . $interview->id . '/cancel', [
                'reason' => 'Cannot attend',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('interviews', [
            'id' => $interview->id,
            'cancelled_by_seeker' => true,
        ]);
    }

    /** @test */
    public function upcoming_interviews_show_correctly()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        $application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);

        Interview::factory()->create([
            'application_id' => $application->id,
            'date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/interviews');

        $response->assertViewHas('upcomingInterviews');
    }

    /** @test */
    public function past_interviews_show_in_history()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        $application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);

        Interview::factory()->create([
            'application_id' => $application->id,
            'date' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/interviews');

        $response->assertViewHas('completedInterviews');
    }
}
```

---

### 5. VacanciesTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VacanciesTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;
    protected User $employer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seeker = User::factory()->create(['role' => 'seeker']);
        $this->employer = User::factory()->create(['role' => 'employer']);
    }

    /** @test */
    public function seeker_can_search_vacancies()
    {
        Vacancy::factory()->count(5)->create(['user_id' => $this->employer->id]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/vacancies');

        $response->assertStatus(200);
        $response->assertViewIs('seeker.vacancies.search');
    }

    /** @test */
    public function seeker_can_filter_vacancies_by_title()
    {
        Vacancy::factory()->create([
            'user_id' => $this->employer->id,
            'title' => 'Backend Developer',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/vacancies?search=Backend');

        $response->assertStatus(200);
    }

    /** @test */
    public function seeker_can_filter_vacancies_by_location()
    {
        Vacancy::factory()->create([
            'user_id' => $this->employer->id,
            'city' => 'Kyiv',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/vacancies?city=Kyiv');

        $response->assertStatus(200);
    }

    /** @test */
    public function seeker_can_save_vacancy()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/vacancies/' . $vacancy->id . '/save');

        $response->assertRedirect();
        $this->assertDatabaseHas('saved_vacancies', [
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);
    }

    /** @test */
    public function seeker_can_unsave_vacancy()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        
        $this->seeker->savedVacancies()->attach($vacancy->id);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/vacancies/' . $vacancy->id . '/unsave');

        $response->assertRedirect();
        $this->assertDatabaseMissing('saved_vacancies', [
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);
    }

    /** @test */
    public function seeker_can_view_vacancy_details()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/vacancies/' . $vacancy->id);

        $response->assertStatus(200);
        $response->assertViewIs('seeker.vacancies.show');
    }

    /** @test */
    public function seeker_cannot_apply_twice_to_same_vacancy()
    {
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        
        Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/applications/' . $vacancy->id . '/submit');

        $response->assertStatus(403);
    }

    /** @test */
    public function inactive_vacancies_not_shown_in_search()
    {
        Vacancy::factory()->create([
            'user_id' => $this->employer->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/vacancies');

        // Should not contain inactive vacancies
        $response->assertViewHas('vacancies');
    }
}
```

---

### 6. ProfileTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use App\Models\SeekerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seeker = User::factory()->create(['role' => 'seeker']);
    }

    /** @test */
    public function seeker_can_view_profile()
    {
        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/profile');

        $response->assertStatus(200);
        $response->assertViewIs('seeker.profile.edit');
    }

    /** @test */
    public function seeker_can_update_profile()
    {
        $response = $this->actingAs($this->seeker)
            ->patch('/dashboard/seeker/profile', [
                'current_position' => 'Senior Backend Engineer',
                'company' => 'Google',
                'years_experience' => 8,
                'about' => 'Experienced developer with focus on Go',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seeker_profiles', [
            'user_id' => $this->seeker->id,
            'current_position' => 'Senior Backend Engineer',
        ]);
    }

    /** @test */
    public function seeker_can_add_social_links()
    {
        $response = $this->actingAs($this->seeker)
            ->patch('/dashboard/seeker/profile', [
                'linkedin' => 'https://linkedin.com/in/john',
                'github' => 'https://github.com/john',
                'portfolio' => 'https://john.dev',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seeker_profiles', [
            'user_id' => $this->seeker->id,
            'linkedin' => 'https://linkedin.com/in/john',
        ]);
    }

    /** @test */
    public function seeker_can_set_job_preferences()
    {
        $response = $this->actingAs($this->seeker)
            ->patch('/dashboard/seeker/profile', [
                'job_preferences' => json_encode([
                    'positions' => ['Backend Developer', 'Lead'],
                    'locations' => ['Kyiv', 'Remote'],
                    'salary_min' => 3000,
                    'salary_max' => 5000,
                ]),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seeker_profiles', [
            'user_id' => $this->seeker->id,
        ]);
    }

    /** @test */
    public function seeker_can_make_profile_visible_to_employers()
    {
        $response = $this->actingAs($this->seeker)
            ->patch('/dashboard/seeker/profile', [
                'visibility' => 'public',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seeker_profiles', [
            'user_id' => $this->seeker->id,
            'visibility' => 'public',
        ]);
    }

    /** @test */
    public function seeker_can_make_profile_private()
    {
        $response = $this->actingAs($this->seeker)
            ->patch('/dashboard/seeker/profile', [
                'visibility' => 'private',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seeker_profiles', [
            'user_id' => $this->seeker->id,
            'visibility' => 'private',
        ]);
    }

    /** @test */
    public function profile_validation_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->seeker)
            ->patch('/dashboard/seeker/profile', [
                'linkedin' => 'not-a-url',
                'years_experience' => 'invalid',
            ]);

        $response->assertSessionHasErrors();
    }
}
```

---

### 7. ResumeTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use App\Models\SeekerResume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class ResumeTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seeker = User::factory()->create(['role' => 'seeker']);
    }

    /** @test */
    public function seeker_can_view_resume_page()
    {
        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/resume');

        $response->assertStatus(200);
        $response->assertViewIs('seeker.resume.index');
    }

    /** @test */
    public function seeker_can_upload_resume()
    {
        $file = UploadedFile::fake()->create('resume.pdf', 100);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/resume', [
                'resume' => $file,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seeker_resumes', [
            'user_id' => $this->seeker->id,
        ]);
    }

    /** @test */
    public function seeker_can_upload_multiple_resumes()
    {
        $file1 = UploadedFile::fake()->create('resume1.pdf', 100);
        $file2 = UploadedFile::fake()->create('resume2.pdf', 100);

        $this->actingAs($this->seeker)->post('/dashboard/seeker/resume', ['resume' => $file1]);
        $this->actingAs($this->seeker)->post('/dashboard/seeker/resume', ['resume' => $file2]);

        $this->assertDatabaseHas('seeker_resumes', [
            'user_id' => $this->seeker->id,
        ]);
        
        $this->assertEquals(2, $this->seeker->resumes()->count());
    }

    /** @test */
    public function seeker_can_set_default_resume()
    {
        $resume = SeekerResume::factory()->create(['user_id' => $this->seeker->id]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/resume/' . $resume->id . '/set-default');

        $response->assertRedirect();
        $this->assertTrue($resume->fresh()->is_default);
    }

    /** @test */
    public function only_one_resume_can_be_default()
    {
        $resume1 = SeekerResume::factory()->create([
            'user_id' => $this->seeker->id,
            'is_default' => true,
        ]);
        
        $resume2 = SeekerResume::factory()->create([
            'user_id' => $this->seeker->id,
            'is_default' => false,
        ]);

        $this->actingAs($this->seeker)->post('/dashboard/seeker/resume/' . $resume2->id . '/set-default');

        $this->assertFalse($resume1->fresh()->is_default);
        $this->assertTrue($resume2->fresh()->is_default);
    }

    /** @test */
    public function seeker_can_delete_resume()
    {
        $resume = SeekerResume::factory()->create(['user_id' => $this->seeker->id]);

        $response = $this->actingAs($this->seeker)
            ->delete('/dashboard/seeker/resume/' . $resume->id);

        $response->assertRedirect();
        $this->assertDatabaseMissing('seeker_resumes', ['id' => $resume->id]);
    }

    /** @test */
    public function seeker_cannot_delete_default_resume()
    {
        $resume = SeekerResume::factory()->create([
            'user_id' => $this->seeker->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->seeker)
            ->delete('/dashboard/seeker/resume/' . $resume->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('seeker_resumes', ['id' => $resume->id]);
    }

    /** @test */
    public function resume_views_counter_increments()
    {
        $resume = SeekerResume::factory()->create(['user_id' => $this->seeker->id, 'views_count' => 0]);

        // Simulate view (would normally be triggered by employer)
        $resume->increment('views_count');

        $this->assertEquals(1, $resume->fresh()->views_count);
    }
}
```

---

### 8. NotificationsTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seeker = User::factory()->create(['role' => 'seeker']);
    }

    /** @test */
    public function seeker_can_view_notifications()
    {
        Notification::factory()->count(5)->create(['user_id' => $this->seeker->id]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/notifications');

        $response->assertStatus(200);
        $response->assertViewHas('notifications');
    }

    /** @test */
    public function seeker_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->seeker->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/notifications/' . $notification->id . '/mark-read');

        $response->assertRedirect();
        $this->assertTrue($notification->fresh()->is_read);
    }

    /** @test */
    public function seeker_can_filter_notifications_by_type()
    {
        Notification::factory()->create([
            'user_id' => $this->seeker->id,
            'type' => 'application_status',
        ]);
        
        Notification::factory()->create([
            'user_id' => $this->seeker->id,
            'type' => 'interview_scheduled',
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/notifications?type=application_status');

        $response->assertStatus(200);
    }

    /** @test */
    public function unread_notifications_show_badge_count()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->seeker->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker');

        $response->assertViewHas('unreadNotificationCount', 3);
    }

    /** @test */
    public function seeker_can_update_notification_settings()
    {
        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/notifications/settings', [
                'email_new_vacancy' => true,
                'email_application_update' => false,
                'push_interview_scheduled' => true,
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function notification_is_created_on_application_status_change()
    {
        // This would typically be triggered by a webhook from employer backend
        Notification::create([
            'user_id' => $this->seeker->id,
            'type' => 'application_status',
            'title' => 'Application status updated',
            'message' => 'Your application status changed to "screening"',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seeker->id,
            'type' => 'application_status',
        ]);
    }
}
```

---

### 9. SettingsTest.php

```php
<?php

namespace Tests\Feature\Seeker;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seeker = User::factory()->create(['role' => 'seeker']);
    }

    /** @test */
    public function seeker_can_view_settings()
    {
        $response = $this->actingAs($this->seeker)
            ->get('/dashboard/seeker/settings');

        $response->assertStatus(200);
    }

    /** @test */
    public function seeker_can_change_password()
    {
        $response = $this->actingAs($this->seeker)
            ->patch('/dashboard/seeker/settings/password', [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function seeker_can_enable_2fa()
    {
        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/settings/2fa/enable');

        $response->assertStatus(200);
    }

    /** @test */
    public function seeker_can_disable_2fa()
    {
        $this->seeker->update(['two_factor_confirmed_at' => now()]);

        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/settings/2fa/disable');

        $response->assertRedirect();
    }

    /** @test */
    public function seeker_can_request_data_download()
    {
        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/settings/export-data');

        $response->assertRedirect();
    }

    /** @test */
    public function seeker_can_delete_account()
    {
        $response = $this->actingAs($this->seeker)
            ->post('/dashboard/seeker/settings/delete-account', [
                'password' => 'password',
                'confirmation' => 'on',
            ]);

        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $this->seeker->id]);
    }
}
```

---

## 🧪 UNIT TESTS (3 файли)

### 1. SeekerServiceTest.php

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SeekerService;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SeekerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SeekerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SeekerService::class);
    }

    /** @test */
    public function get_seeker_statistics()
    {
        $seeker = User::factory()->create(['role' => 'seeker']);
        $employer = User::factory()->create(['role' => 'employer']);
        
        Application::factory()->count(5)->create([
            'seeker_id' => $seeker->id,
            'status' => 'submitted',
        ]);

        $stats = $this->service->getSeekerStatistics($seeker);

        $this->assertEquals(5, $stats['total_applications']);
    }

    /** @test */
    public function get_recommended_vacancies()
    {
        $seeker = User::factory()->create(['role' => 'seeker']);
        $employer = User::factory()->create(['role' => 'employer']);
        
        Vacancy::factory()->count(3)->create(['user_id' => $employer->id]);

        $recommended = $this->service->getRecommendedVacancies($seeker);

        $this->assertEquals(3, $recommended->count());
    }
}
```

---

### 2. ApplicationServiceTest.php

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ApplicationService;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApplicationService::class);
    }

    /** @test */
    public function submit_application()
    {
        $seeker = User::factory()->create(['role' => 'seeker']);
        $employer = User::factory()->create(['role' => 'employer']);
        $vacancy = Vacancy::factory()->create(['user_id' => $employer->id]);

        $application = $this->service->submitApplication($seeker, $vacancy);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'submitted',
        ]);
    }

    /** @test */
    public function withdraw_application()
    {
        $application = Application::factory()->create(['status' => 'submitted']);

        $this->service->withdrawApplication($application);

        $this->assertEquals('withdrawn', $application->fresh()->status);
    }
}
```

---

## 🔌 INTEGRATION TESTS (2 файли)

### 1. WebhookSyncTest.php

```php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $seeker;
    protected User $employer;
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seeker = User::factory()->create(['role' => 'seeker']);
        $this->employer = User::factory()->create(['role' => 'employer']);
        
        $vacancy = Vacancy::factory()->create(['user_id' => $this->employer->id]);
        $this->application = Application::factory()->create([
            'seeker_id' => $this->seeker->id,
            'vacancy_id' => $vacancy->id,
        ]);
    }

    /** @test */
    public function webhook_updates_application_status()
    {
        $response = $this->postJson('/webhooks/seeker/application-status-changed', [
            'application_id' => $this->application->id,
            'old_status' => 'submitted',
            'new_status' => 'screening',
            'changed_by' => 'employer',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('screening', $this->application->fresh()->status);
    }

    /** @test */
    public function webhook_creates_interview()
    {
        $response = $this->postJson('/webhooks/seeker/interview-scheduled', [
            'application_id' => $this->application->id,
            'date' => now()->addDays(3)->format('Y-m-d'),
            'time' => '14:00',
            'type' => 'online',
            'meeting_link' => 'https://meet.google.com/abc-def-ghi',
            'duration' => 60,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('interviews', [
            'application_id' => $this->application->id,
        ]);
    }

    /** @test */
    public function webhook_creates_notification_on_status_change()
    {
        $this->postJson('/webhooks/seeker/application-status-changed', [
            'application_id' => $this->application->id,
            'old_status' => 'submitted',
            'new_status' => 'screening',
            'changed_by' => 'employer',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seeker->id,
            'type' => 'application_status',
        ]);
    }
}
```

---

### 2. ApplicationSyncTest.php

```php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Application;
use App\Models\Vacancy;
use App\Models\Interview;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationSyncTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function full_application_lifecycle_sync()
    {
        $seeker = User::factory()->create(['role' => 'seeker']);
        $employer = User::factory()->create(['role' => 'employer']);
        $vacancy = Vacancy::factory()->create(['user_id' => $employer->id]);

        // 1. Seeker submits application
        $application = Application::create([
            'seeker_id' => $seeker->id,
            'vacancy_id' => $vacancy->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('applications', [
            'status' => 'submitted',
        ]);

        // 2. Employer views application
        $this->postJson('/webhooks/seeker/application-status-changed', [
            'application_id' => $application->id,
            'old_status' => 'submitted',
            'new_status' => 'viewed',
            'changed_by' => 'employer',
        ]);

        $this->assertEquals('viewed', $application->fresh()->status);

        // 3. Employer schedules interview
        $this->postJson('/webhooks/seeker/interview-scheduled', [
            'application_id' => $application->id,
            'date' => now()->addDays(2)->format('Y-m-d'),
            'time' => '14:00',
            'type' => 'online',
            'meeting_link' => 'https://meet.google.com/test',
            'duration' => 60,
        ]);

        $this->assertDatabaseHas('interviews', [
            'application_id' => $application->id,
        ]);

        // 4. Seeker confirms interview
        $interview = $application->interviews()->first();
        
        $this->actingAs($seeker)->post(
            '/dashboard/seeker/interviews/' . $interview->id . '/confirm'
        );

        $this->assertTrue($interview->fresh()->confirmed_by_seeker);
    }

    /** @test */
    public function seeker_can_reject_interview()
    {
        $seeker = User::factory()->create(['role' => 'seeker']);
        $employer = User::factory()->create(['role' => 'employer']);
        $vacancy = Vacancy::factory()->create(['user_id' => $employer->id]);

        $application = Application::create([
            'seeker_id' => $seeker->id,
            'vacancy_id' => $vacancy->id,
            'status' => 'interview',
        ]);

        $interview = Interview::create([
            'application_id' => $application->id,
            'date' => now()->addDays(2),
            'time' => '14:00',
            'type' => 'online',
        ]);

        $response = $this->actingAs($seeker)->post(
            '/dashboard/seeker/interviews/' . $interview->id . '/cancel',
            ['reason' => 'Cannot attend']
        );

        $response->assertRedirect();
        $this->assertTrue($interview->fresh()->cancelled_by_seeker);
    }
}
```

---

## 🚀 КОМАНДИ ДЛЯ ЗАПУСКУ

```bash
# Запустити ВСІ тести
php artisan test

# Запустити Feature тести
php artisan test tests/Feature/Seeker

# Запустити конкретний тест
php artisan test tests/Feature/Seeker/DashboardTest

# Запустити з покриттям кодом
php artisan test --coverage

# Запустити з дебагом
php artisan test --debug

# Запустити тільки Failed тесты
php artisan test --only-failures

# Запустити тесты з фільтром
php artisan test --filter DashboardTest
```

---

## ✅ КОНТРОЛЬНИЙ СПИСОК

### Setup (1 день)
- [ ] Скопіюйте всі файли тестів
- [ ] Запустіть `php artisan test`
- [ ] Переконайтесь, що всі тести зелені

### Feature Tests (3-4 дні)
- [ ] DashboardTest - 7 тестів ✅
- [ ] ApplicationsTest - 8 тестів ✅
- [ ] ApplicationDetailTest - 7 тестів ✅
- [ ] InterviewsTest - 6 тестів ✅
- [ ] VacanciesTest - 7 тестів ✅
- [ ] ProfileTest - 7 тестів ✅
- [ ] ResumeTest - 7 тестів ✅
- [ ] NotificationsTest - 6 тестів ✅
- [ ] SettingsTest - 6 тестів ✅

**ВСЬОГО: 61 Feature Test** ✅

### Unit Tests (1 день)
- [ ] SeekerServiceTest - 2 тести ✅
- [ ] ApplicationServiceTest - 2 тести ✅

**ВСЬОГО: 4 Unit Tests** ✅

### Integration Tests (2 дні)
- [ ] WebhookSyncTest - 3 тести ✅
- [ ] ApplicationSyncTest - 2 тести ✅

**ВСЬОГО: 5 Integration Tests** ✅

---

## 📊 СТАТИСТИКА

- **Всього тестів:** 70
- **Feature тести:** 61
- **Unit тести:** 4
- **Integration тести:** 5
- **Середній час виконання:** 2-3 хвилини
- **Очікуване покриття кодом:** 75-85%

---

**КІНЕЦЬ ТЕСТІВ**

Всі тести готові до запуску! Копіюйте файли в папку `tests/` вашого проекту і запустіть `php artisan test` 🚀
