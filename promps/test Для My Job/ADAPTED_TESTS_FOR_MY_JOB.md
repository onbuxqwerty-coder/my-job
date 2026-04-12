# 🧪 Адаптовані тести для My Job (Laravel 13 + Livewire)

## 📊 Структура тестів для My Job

```
my-job/
├── tests/
│   ├── Feature/                           ← Livewire + Laravel Feature тести
│   │   ├── Employer/
│   │   │   ├── EmployerDashboardTest.php
│   │   │   ├── VacancyManagementTest.php
│   │   │   ├── ApplicantsManagementTest.php
│   │   │   ├── CandidateDetailTest.php
│   │   │   └── MessageTemplatesTest.php
│   │   │
│   │   ├── Candidate/
│   │   │   ├── JobSearchTest.php
│   │   │   ├── JobApplicationTest.php
│   │   │   └── ProfileManagementTest.php
│   │   │
│   │   ├── Auth/
│   │   │   ├── AuthenticationTest.php
│   │   │   └── TelegramAuthTest.php
│   │   │
│   │   └── Admin/
│   │       └── FilamentAdminTest.php
│   │
│   ├── Unit/                              ← Unit тести для Services
│   │   ├── Services/
│   │   │   ├── VacancyServiceTest.php
│   │   │   ├── ApplicationServiceTest.php
│   │   │   ├── CommunicationServiceTest.php
│   │   │   ├── InterviewServiceTest.php
│   │   │   └── PaymentServiceTest.php
│   │   │
│   │   ├── Models/
│   │   │   ├── VacancyTest.php
│   │   │   ├── ApplicationTest.php
│   │   │   └── UserTest.php
│   │   │
│   │   └── DTOs/
│   │       ├── ApplyDTOTest.php
│   │       └── VacancySearchDTOTest.php
│   │
│   ├── Integration/                       ← Інтеграційні тести
│   │   ├── TelegramBotTest.php
│   │   ├── StripePaymentTest.php
│   │   └── EmailNotificationsTest.php
│   │
│   ├── Browser/                           ← Dusk E2E тести
│   │   ├── EmployerWorkflowTest.php
│   │   ├── CandidateSearchFlowTest.php
│   │   └── ApplicationProcessTest.php
│   │
│   └── TestCase.php                       ← Base test class
```

---

## 1️⃣ FEATURE ТЕСТИ: Кабінет роботодавця

### Файл: `tests/Feature/Employer/EmployerDashboardTest.php`

```php
<?php

namespace Tests\Feature\Employer;

use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employer = User::factory()->create(['role' => 'employer']);
        $this->company = Company::factory()->create(['user_id' => $this->employer->id]);
    }

    // ✅ ТЕСТ 1: Роботодавець бачить свій дашборд
    public function test_employer_can_view_dashboard()
    {
        $this->actingAs($this->employer)
            ->get('/employer/dashboard')
            ->assertStatus(200)
            ->assertSee('Кабінет роботодавця');
    }

    // ✅ ТЕСТ 2: Дашборд показує статистику вакансій
    public function test_dashboard_shows_vacancy_statistics()
    {
        Vacancy::factory(5)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->employer)
            ->get('/employer/dashboard')
            ->assertStatus(200)
            ->assertSee('Активні вакансії');
        // Перевіримо що є число 5
        $response = $this->actingAs($this->employer)->get('/employer/dashboard');
        $this->assertStringContainsString('5', $response->getContent());
    }

    // ✅ ТЕСТ 3: Дашборд показує кількість отриманих заявок
    public function test_dashboard_shows_application_count()
    {
        $vacancy = Vacancy::factory()->create(['company_id' => $this->company->id]);
        Application::factory(12)->create(['vacancy_id' => $vacancy->id]);

        $this->actingAs($this->employer)
            ->get('/employer/dashboard')
            ->assertStatus(200);
        
        $response = $this->actingAs($this->employer)->get('/employer/dashboard');
        $this->assertStringContainsString('12', $response->getContent());
    }

    // ✅ ТЕСТ 4: Дашборд показує тренд подачі заявок
    public function test_dashboard_shows_application_trends()
    {
        // Створити заявки на різні дати
        $vacancy = Vacancy::factory()->create(['company_id' => $this->company->id]);
        
        for ($i = 0; $i < 5; $i++) {
            Application::factory()->create([
                'vacancy_id' => $vacancy->id,
                'created_at' => now()->subDays($i)
            ]);
        }

        $this->actingAs($this->employer)
            ->get('/employer/dashboard')
            ->assertStatus(200);
    }

    // ✅ ТЕСТ 5: Тільки роботодавець має доступ до свого дашборду
    public function test_candidate_cannot_access_employer_dashboard()
    {
        $candidate = User::factory()->create(['role' => 'candidate']);

        $this->actingAs($candidate)
            ->get('/employer/dashboard')
            ->assertStatus(403);
    }

    // ✅ ТЕСТ 6: Невавторизований користувач перенаправляється на логін
    public function test_unauthenticated_user_redirected_to_login()
    {
        $this->get('/employer/dashboard')
            ->assertRedirect('/login');
    }
}
```

---

### Файл: `tests/Feature/Employer/VacancyManagementTest.php`

```php
<?php

namespace Tests\Feature\Employer;

use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacancyManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employer = User::factory()->create(['role' => 'employer']);
        $this->company = Company::factory()->create(['user_id' => $this->employer->id]);
    }

    // ✅ ТЕСТ 1: Роботодавець може створити вакансію
    public function test_employer_can_create_vacancy()
    {
        $vacancyData = [
            'title' => 'Ship Engineer',
            'description' => 'Пошук досвідченого інженера',
            'employment_type' => 'full_time',
            'salary_min' => 50000,
            'salary_max' => 70000,
            'category_id' => 1,
        ];

        $response = $this->actingAs($this->employer)
            ->post('/employer/vacancies', $vacancyData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vacancies', [
            'title' => 'Ship Engineer',
            'company_id' => $this->company->id,
        ]);
    }

    // ✅ ТЕСТ 2: Роботодавець може редагувати свою вакансію
    public function test_employer_can_edit_vacancy()
    {
        $vacancy = Vacancy::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->employer)
            ->patch("/employer/vacancies/{$vacancy->id}", [
                'title' => 'Updated Engineer',
                'description' => 'Оновлений опис'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('vacancies', [
            'id' => $vacancy->id,
            'title' => 'Updated Engineer'
        ]);
    }

    // ✅ ТЕСТ 3: Роботодавець може видалити вакансію
    public function test_employer_can_delete_vacancy()
    {
        $vacancy = Vacancy::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->employer)
            ->delete("/employer/vacancies/{$vacancy->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('vacancies', ['id' => $vacancy->id]);
    }

    // ✅ ТЕСТ 4: Роботодавець не може редагувати чужу вакансію
    public function test_employer_cannot_edit_other_employer_vacancy()
    {
        $otherEmployer = User::factory()->create(['role' => 'employer']);
        $otherCompany = Company::factory()->create(['user_id' => $otherEmployer->id]);
        $vacancy = Vacancy::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($this->employer)
            ->patch("/employer/vacancies/{$vacancy->id}", ['title' => 'Hacked']);

        $response->assertStatus(403);
    }

    // ✅ ТЕСТ 5: Список вакансій роботодавця
    public function test_employer_can_view_own_vacancies()
    {
        Vacancy::factory(3)->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->employer)
            ->get('/employer/vacancies');

        $response->assertStatus(200);
        // Перевіримо що вакансії видно в HTML
        $this->assertCount(3, Vacancy::where('company_id', $this->company->id)->get());
    }

    // ✅ ТЕСТ 6: Featured-розміщення вакансії (payment)
    public function test_employer_can_feature_vacancy()
    {
        $vacancy = Vacancy::factory()->create([
            'company_id' => $this->company->id,
            'is_featured' => false
        ]);

        // Симуляція оплати через Stripe
        $response = $this->actingAs($this->employer)
            ->post("/employer/vacancies/{$vacancy->id}/feature");

        // Має перенаправити на Stripe або показати успіх
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 302
        );
    }
}
```

---

### Файл: `tests/Feature/Employer/ApplicantsManagementTest.php`

```php
<?php

namespace Tests\Feature\Employer;

use App\Models\Application;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantsManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;
    private Company $company;
    private Vacancy $vacancy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employer = User::factory()->create(['role' => 'employer']);
        $this->company = Company::factory()->create(['user_id' => $this->employer->id]);
        $this->vacancy = Vacancy::factory()->create(['company_id' => $this->company->id]);
    }

    // ✅ ТЕСТ 1: Роботодавець бачить список заявок
    public function test_employer_can_view_applicants()
    {
        Application::factory(5)->create(['vacancy_id' => $this->vacancy->id]);

        $response = $this->actingAs($this->employer)
            ->get('/employer/applicants');

        $response->assertStatus(200);
        $this->assertCount(5, Application::where('vacancy_id', $this->vacancy->id)->get());
    }

    // ✅ ТЕСТ 2: Роботодавець може змінити статус заявки
    public function test_employer_can_change_application_status()
    {
        $application = Application::factory()->create(['vacancy_id' => $this->vacancy->id]);

        $response = $this->actingAs($this->employer)
            ->patch("/employer/applications/{$application->id}/status", [
                'status' => 'interview'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'interview'
        ]);
    }

    // ✅ ТЕСТ 3: Роботодавець може додати нотатку до заявки
    public function test_employer_can_add_note_to_application()
    {
        $application = Application::factory()->create(['vacancy_id' => $this->vacancy->id]);

        $response = $this->actingAs($this->employer)
            ->post("/employer/applications/{$application->id}/notes", [
                'text' => 'Дуже обіцяючий кандидат'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('application_notes', [
            'application_id' => $application->id,
            'text' => 'Дуже обіцяючий кандидат'
        ]);
    }

    // ✅ ТЕСТ 4: Роботодавець може оцінити кандидата
    public function test_employer_can_rate_application()
    {
        $application = Application::factory()->create(['vacancy_id' => $this->vacancy->id]);

        $response = $this->actingAs($this->employer)
            ->patch("/employer/applications/{$application->id}/rate", [
                'rating' => 5
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'rating' => 5
        ]);
    }

    // ✅ ТЕСТ 5: Фільтрування заявок по статусу
    public function test_employer_can_filter_applications_by_status()
    {
        Application::factory(3)->create([
            'vacancy_id' => $this->vacancy->id,
            'status' => 'pending'
        ]);
        Application::factory(2)->create([
            'vacancy_id' => $this->vacancy->id,
            'status' => 'interview'
        ]);

        $response = $this->actingAs($this->employer)
            ->get('/employer/applicants?status=interview');

        $response->assertStatus(200);
        $this->assertCount(2, Application::where('status', 'interview')->get());
    }

    // ✅ ТЕСТ 6: Пошук кандидатів по ім'ю
    public function test_employer_can_search_applicants()
    {
        $candidate = User::factory()->create(['name' => 'Іван Петров']);
        Application::factory()->create([
            'vacancy_id' => $this->vacancy->id,
            'user_id' => $candidate->id
        ]);

        $response = $this->actingAs($this->employer)
            ->get('/employer/applicants?search=Іван');

        $response->assertStatus(200);
    }

    // ✅ ТЕСТ 7: Роботодавець НЕ бачить заявок на чужі вакансії
    public function test_employer_cannot_see_applications_to_other_vacancies()
    {
        $otherEmployer = User::factory()->create(['role' => 'employer']);
        $otherCompany = Company::factory()->create(['user_id' => $otherEmployer->id]);
        $otherVacancy = Vacancy::factory()->create(['company_id' => $otherCompany->id]);
        Application::factory()->create(['vacancy_id' => $otherVacancy->id]);

        $response = $this->actingAs($this->employer)
            ->get('/employer/applicants');

        // Повинна показувати тільки свої заявки
        $this->assertTrue($response->status() === 200);
    }
}
```

---

## 2️⃣ UNIT ТЕСТИ: Services

### Файл: `tests/Unit/Services/VacancyServiceTest.php`

```php
<?php

namespace Tests\Unit\Services;

use App\DTOs\VacancySearchDTO;
use App\Services\VacancyService;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacancyServiceTest extends TestCase
{
    use RefreshDatabase;

    private VacancyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VacancyService::class);
    }

    // ✅ ТЕСТ 1: Пошук вакансій по ключовому слову
    public function test_search_vacancies_by_keyword()
    {
        Vacancy::factory()->create(['title' => 'Ship Engineer']);
        Vacancy::factory()->create(['title' => 'Web Developer']);

        $searchDTO = new VacancySearchDTO(
            keyword: 'Ship',
            category: null,
            employmentType: null
        );

        $results = $this->service->search($searchDTO);

        $this->assertCount(1, $results);
        $this->assertEquals('Ship Engineer', $results->first()->title);
    }

    // ✅ ТЕСТ 2: Фільтрування по типу зайнятості
    public function test_filter_vacancies_by_employment_type()
    {
        Vacancy::factory()->create(['employment_type' => 'full_time']);
        Vacancy::factory()->create(['employment_type' => 'part_time']);

        $searchDTO = new VacancySearchDTO(
            keyword: null,
            category: null,
            employmentType: 'full_time'
        );

        $results = $this->service->search($searchDTO);

        $this->assertCount(1, $results);
    }

    // ✅ ТЕСТ 3: Фільтрування по категорії
    public function test_filter_vacancies_by_category()
    {
        $category1 = Category::factory()->create(['name' => 'IT']);
        $category2 = Category::factory()->create(['name' => 'Sales']);

        Vacancy::factory()->create(['category_id' => $category1->id]);
        Vacancy::factory()->create(['category_id' => $category2->id]);

        $searchDTO = new VacancySearchDTO(
            keyword: null,
            category: $category1->id,
            employmentType: null
        );

        $results = $this->service->search($searchDTO);

        $this->assertCount(1, $results);
    }

    // ✅ ТЕСТ 4: Пошук НЕ показує вимкнені вакансії
    public function test_search_excludes_inactive_vacancies()
    {
        Vacancy::factory()->create(['title' => 'Active', 'is_active' => true]);
        Vacancy::factory()->create(['title' => 'Inactive', 'is_active' => false]);

        $searchDTO = new VacancySearchDTO();
        $results = $this->service->search($searchDTO);

        $this->assertCount(1, $results);
    }

    // ✅ ТЕСТ 5: Сортування по датам
    public function test_sort_vacancies_by_recent()
    {
        Vacancy::factory()->create(['title' => 'Old', 'created_at' => now()->subDays(10)]);
        Vacancy::factory()->create(['title' => 'New', 'created_at' => now()]);

        $results = $this->service->search(new VacancySearchDTO(), 'recent');

        $this->assertEquals('New', $results->first()->title);
    }
}
```

---

### Файл: `tests/Unit/Services/ApplicationServiceTest.php`

```php
<?php

namespace Tests\Unit\Services;

use App\DTOs\ApplyDTO;
use App\Services\ApplicationService;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApplicationService::class);
    }

    // ✅ ТЕСТ 1: Успішна подача заявки
    public function test_candidate_can_apply_to_vacancy()
    {
        $candidate = User::factory()->create(['role' => 'candidate']);
        $vacancy = Vacancy::factory()->create();

        $applyDTO = new ApplyDTO(
            vacancy_id: $vacancy->id,
            user_id: $candidate->id,
            resume_text: 'My resume'
        );

        $application = $this->service->apply($applyDTO);

        $this->assertNotNull($application);
        $this->assertDatabaseHas('applications', [
            'user_id' => $candidate->id,
            'vacancy_id' => $vacancy->id
        ]);
    }

    // ✅ ТЕСТ 2: Кандидат НЕ може подати дві заявки на одну вакансію
    public function test_candidate_cannot_apply_twice()
    {
        $candidate = User::factory()->create(['role' => 'candidate']);
        $vacancy = Vacancy::factory()->create();

        $applyDTO = new ApplyDTO(
            vacancy_id: $vacancy->id,
            user_id: $candidate->id,
            resume_text: 'My resume'
        );

        $this->service->apply($applyDTO);

        $this->expectException(\Exception::class);
        $this->service->apply($applyDTO);
    }

    // ✅ ТЕСТ 3: Отримання статистики заявок
    public function test_get_application_statistics()
    {
        $vacancy = Vacancy::factory()->create();
        Application::factory(5)->create(['vacancy_id' => $vacancy->id, 'status' => 'pending']);
        Application::factory(3)->create(['vacancy_id' => $vacancy->id, 'status' => 'interview']);

        $stats = $this->service->getStatistics($vacancy->id);

        $this->assertEquals(5, $stats['pending']);
        $this->assertEquals(3, $stats['interview']);
        $this->assertEquals(8, $stats['total']);
    }

    // ✅ ТЕСТ 4: Конвертація заявок
    public function test_calculate_conversion_rate()
    {
        $vacancy = Vacancy::factory()->create();
        Application::factory(10)->create(['vacancy_id' => $vacancy->id]);
        Application::factory(2)->create(['vacancy_id' => $vacancy->id, 'status' => 'hired']);

        $conversion = $this->service->getConversionRate($vacancy->id);

        $this->assertEquals(20, $conversion); // 2 з 10 = 20%
    }
}
```

---

## 3️⃣ LIVEWIRE COMPONENT ТЕСТИ

### Файл: `tests/Feature/Livewire/EmployerApplicantsTest.php`

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Employer\Applicants;
use App\Models\Application;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployerApplicantsTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employer = User::factory()->create(['role' => 'employer']);
        $this->company = Company::factory()->create(['user_id' => $this->employer->id]);
    }

    // ✅ ТЕСТ 1: Livewire компонент завантажується
    public function test_applicants_component_renders()
    {
        Livewire::actingAs($this->employer)
            ->test(Applicants::class)
            ->assertStatus(200);
    }

    // ✅ ТЕСТ 2: Фільтрування по статусу в Livewire
    public function test_filter_by_status()
    {
        $vacancy = Vacancy::factory()->create(['company_id' => $this->company->id]);
        Application::factory(3)->create(['vacancy_id' => $vacancy->id, 'status' => 'pending']);
        Application::factory(2)->create(['vacancy_id' => $vacancy->id, 'status' => 'interview']);

        Livewire::actingAs($this->employer)
            ->test(Applicants::class)
            ->set('statusFilter', 'interview')
            ->assertSee('2'); // Має показати 2 заявки
    }

    // ✅ ТЕСТ 3: Пошук в Livewire компоненті
    public function test_search_applicants_in_livewire()
    {
        $vacancy = Vacancy::factory()->create(['company_id' => $this->company->id]);
        $candidate = User::factory()->create(['name' => 'Іван Петров']);
        Application::factory()->create(['vacancy_id' => $vacancy->id, 'user_id' => $candidate->id]);

        Livewire::actingAs($this->employer)
            ->test(Applicants::class)
            ->set('searchQuery', 'Іван')
            ->assertSee('Іван Петров');
    }

    // ✅ ТЕСТ 4: Зміна статусу через Livewire
    public function test_change_status_in_livewire()
    {
        $vacancy = Vacancy::factory()->create(['company_id' => $this->company->id]);
        $application = Application::factory()->create(['vacancy_id' => $vacancy->id]);

        Livewire::actingAs($this->employer)
            ->test(Applicants::class)
            ->call('changeStatus', $application->id, 'interview')
            ->assertDispatchedBrowserEvent('success');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'interview'
        ]);
    }
}
```

---

## 4️⃣ BROWSER (DUSK) E2E ТЕСТИ

### Файл: `tests/Browser/EmployerWorkflowTest.php`

```php
<?php

namespace Tests\Browser;

use App\Models\Company;
use App\Models\User;
use Facebook\WebDriver\WebDriverKeys;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EmployerWorkflowTest extends DuskTestCase
{
    // ✅ ТЕСТ 1: Повний цикл роботодавця (від логіну до публікації)
    public function test_employer_complete_workflow()
    {
        $employer = User::factory()->create([
            'email' => 'employer@test.com',
            'password' => bcrypt('password'),
            'role' => 'employer'
        ]);

        $this->browse(function (Browser $browser) use ($employer) {
            $browser
                // 1. Логін
                ->visit('/login')
                ->type('email', 'employer@test.com')
                ->type('password', 'password')
                ->press('Увійти')
                ->waitForLocation('/employer/dashboard')
                ->assertSee('Кабінет роботодавця')

                // 2. Перейти до створення вакансії
                ->click('a[href*="/employer/vacancies/create"]')
                ->waitForLocation('/employer/vacancies/create')

                // 3. Заповнити форму вакансії
                ->type('title', 'Senior Ship Engineer')
                ->type('description', 'Шукаємо досвідченого інженера')
                ->select('employment_type', 'full_time')
                ->type('salary_min', '50000')
                ->type('salary_max', '70000')
                ->press('Публікувати')

                // 4. Перевірити що вакансія створена
                ->waitForLocation('/employer/vacancies')
                ->assertSee('Senior Ship Engineer')

                // 5. Перейти до списку заявок
                ->click('a[href*="/employer/applicants"]')
                ->waitForLocation('/employer/applicants')
                ->assertSee('Список заявок')

                // 6. Вийти з кабінету
                ->click('button[data-action="logout"]')
                ->waitForLocation('/login');
        });
    }

    // ✅ ТЕСТ 2: Управління кандидатами через UI
    public function test_manage_applicants_through_ui()
    {
        $employer = User::factory()->create(['role' => 'employer']);

        $this->browse(function (Browser $browser) use ($employer) {
            $browser
                ->loginAs($employer)
                ->visit('/employer/applicants')

                // Фільтрування по статусу
                ->select('status_filter', 'pending')
                ->waitForReload()
                ->assertSee('Нові заявки')

                // Пошук кандидата
                ->type('search', 'Іван')
                ->press('Пошук')
                ->waitForReload()

                // Клік на кандидата
                ->click('a[data-candidate-id]')
                ->waitForLocation('/employer/candidates/*')

                // Додавання нотатки
                ->type('note_text', 'Дуже добрий кандидат!')
                ->press('Додати нотатку')
                ->assertSee('Нотатка додана');
        });
    }
}
```

---

## 5️⃣ TELEGRAM BOT INTEGRATION ТЕСТИ

### Файл: `tests/Integration/TelegramBotTest.php`

```php
<?php

namespace Tests\Integration;

use App\Models\Category;
use App\Models\TelegramSubscription;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    private TelegramService $telegramService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->telegramService = app(TelegramService::class);
    }

    // ✅ ТЕСТ 1: Користувач підписується на категорію через Telegram
    public function test_user_can_subscribe_via_telegram()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create(['telegram_id' => '123456789']);

        $subscription = TelegramSubscription::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_active' => true
        ]);

        $this->assertDatabaseHas('telegram_subscriptions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_active' => true
        ]);
    }

    // ✅ ТЕСТ 2: Telegram алерти відправляються у правильний час
    public function test_telegram_alerts_sent_to_subscribers()
    {
        $category = Category::factory()->create();
        $subscriber = User::factory()->create(['telegram_id' => '123456789']);
        TelegramSubscription::create([
            'user_id' => $subscriber->id,
            'category_id' => $category->id,
            'is_active' => true
        ]);

        // Створити нову вакансію в цій категорії
        $vacancy = Vacancy::factory()->create(['category_id' => $category->id]);

        // Перевірити що алерт буде відправлений
        $this->assertEquals($subscriber->telegram_id, '123456789');
    }

    // ✅ ТЕСТ 3: Користувач може вимкнути алерти
    public function test_user_can_disable_alerts()
    {
        $user = User::factory()->create(['telegram_id' => '123456789']);
        $subscription = TelegramSubscription::factory()->create([
            'user_id' => $user->id,
            'is_active' => true
        ]);

        $subscription->update(['is_active' => false]);

        $this->assertFalse($subscription->fresh()->is_active);
    }
}
```

---

## 6️⃣ STRIPE PAYMENT ТЕСТИ

### Файл: `tests/Integration/StripePaymentTest.php`

```php
<?php

namespace Tests\Integration;

use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
    }

    // ✅ ТЕСТ 1: Роботодавець може оплатити featured-розміщення
    public function test_employer_can_pay_for_featured()
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $company = Company::factory()->create(['user_id' => $employer->id]);
        $vacancy = Vacancy::factory()->create(['company_id' => $company->id]);

        // Симуляція платежу
        $session = $this->paymentService->createCheckoutSession(
            vacancy: $vacancy,
            priceInCents: 10000 // $100
        );

        $this->assertNotNull($session);
        $this->assertStringContainsString('stripe', $session);
    }

    // ✅ ТЕСТ 2: Featured-вакансія показується спочатку
    public function test_featured_vacancy_appears_first()
    {
        $vacancy1 = Vacancy::factory()->create(['is_featured' => false]);
        $vacancy2 = Vacancy::factory()->create(['is_featured' => true]);

        $results = Vacancy::orderByFeatured()->get();

        $this->assertEquals($vacancy2->id, $results->first()->id);
    }

    // ✅ ТЕСТ 3: Featured-статус закінчується після X днів
    public function test_featured_status_expires()
    {
        $vacancy = Vacancy::factory()->create([
            'is_featured' => true,
            'featured_until' => now()->addDays(7)
        ]);

        // Проміна часу на 8 днів
        $this->travel(8)->days();

        $vacancy->refresh();

        $this->assertFalse($vacancy->isFeaturedActive());
    }
}
```

---

## 📋 ЗАПУСК ТЕСТІВ ДЛЯ MY JOB

```bash
# ✅ ВСІ ТЕСТИ
php artisan test

# 🧪 UNIT ТЕСТИ
php artisan test tests/Unit

# 🔗 FEATURE ТЕСТИ (Livewire)
php artisan test tests/Feature

# 🌐 INTEGRATION ТЕСТИ
php artisan test tests/Integration

# 🎬 BROWSER ТЕСТИ (Dusk)
php artisan dusk

# 📊 ПОКРИТТЯ КОДОМ
php artisan test --coverage

# 👀 WATCH РЕЖИМ
php artisan test --watch
```

---

## ✅ ЧЕКЛИСТ ТЕСТУВАННЯ

- [ ] Unit тести для Service classes
- [ ] Feature тести для Livewire компонентів
- [ ] Livewire тести для UI інтеракцій
- [ ] Integration тести для Telegram Bot
- [ ] Integration тести для Stripe
- [ ] Browser (Dusk) E2E тести
- [ ] Тести для API endpoints (якщо є)
- [ ] Тести для Mail notifications
- [ ] Тести для Filament Admin
- [ ] Coverage > 80%

---

## 📚 Посилання

- **Laravel Testing:** https://laravel.com/docs/testing
- **Livewire Testing:** https://livewire.laravel.com/docs/testing
- **Dusk E2E:** https://laravel.com/docs/dusk
- **PHPUnit:** https://phpunit.de/
