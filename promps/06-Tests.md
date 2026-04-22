# 06 — Tests (PHPUnit + Livewire + Dusk)

## Objetivo
Створити комплексну тестову систему для Resume Builder:
- **PHPUnit тести** для Models і Controllers
- **Livewire тести** для компонентів
- **Dusk тести** для E2E тестування UI

---

## 1. PHPUnit Tests

### Resume Model Tests

```php
// tests/Unit/ResumeTest.php

namespace Tests\Unit;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Resume $resume;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->resume = Resume::factory()->for($this->user)->create();
    }

    /**
     * @test
     */
    public function it_creates_a_resume()
    {
        $this->assertDatabaseHas('resumes', [
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
    }

    /**
     * @test
     */
    public function it_updates_personal_info_without_overwriting()
    {
        $this->resume->updatePersonalInfo(['first_name' => 'Іван']);
        $this->resume->updatePersonalInfo(['last_name' => 'Петренко']);

        $this->assertEquals('Іван', $this->resume->personal_info['first_name']);
        $this->assertEquals('Петренко', $this->resume->personal_info['last_name']);
    }

    /**
     * @test
     */
    public function it_updates_location()
    {
        $this->resume->updateLocation([
            'city' => 'Київ',
            'latitude' => 50.4501,
            'longitude' => 30.5241,
        ]);

        $this->assertEquals('Київ', $this->resume->location['city']);
        $this->assertEquals(50.4501, $this->resume->location['latitude']);
    }

    /**
     * @test
     */
    public function it_validates_publishable_state()
    {
        // Draft резюме без даних — не може бути опубліковано
        $this->assertFalse($this->resume->isPublishable());

        // Додаємо критичні дані
        $this->resume->updatePersonalInfo([
            'first_name' => 'Іван',
            'last_name' => 'Петренко',
            'email' => 'ivan@example.com',
            'email_verified_at' => now(),
        ]);

        // Додаємо мінімум один досвід
        $this->resume->experiences()->create([
            'position' => 'Developer',
            'company_name' => 'TechCorp',
            'start_date' => now()->subYear(),
            'end_date' => now(),
        ]);

        // Тепер резюме готове до публікації
        $this->assertTrue($this->resume->isPublishable());
    }

    /**
     * @test
     */
    public function it_generates_stepper_status()
    {
        $this->resume->updatePersonalInfo([
            'first_name' => 'Іван',
            'last_name' => 'Петренко',
        ]);

        $status = $this->resume->getStepperStatus();

        $this->assertTrue($status['personal_info']);
        $this->assertFalse($status['email']);
        $this->assertFalse($status['experience']);
    }
}
```

### Experience Model Tests

```php
// tests/Unit/ExperienceTest.php

namespace Tests\Unit;

use App\Models\Resume;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperienceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_validates_experience_with_end_date()
    {
        $user = User::factory()->create();
        $resume = Resume::factory()->for($user)->create();

        $experience = $resume->experiences()->create([
            'position' => 'Senior Developer',
            'company_name' => 'TechCorp',
            'start_date' => now()->subYears(2),
            'end_date' => now()->subYear(),
        ]);

        $this->assertTrue($experience->isValid());
    }

    /**
     * @test
     */
    public function it_validates_current_job()
    {
        $user = User::factory()->create();
        $resume = Resume::factory()->for($user)->create();

        $experience = $resume->experiences()->create([
            'position' => 'Developer',
            'company_name' => 'TechCorp',
            'start_date' => now()->subYear(),
            'is_current' => true,
        ]);

        $this->assertTrue($experience->isValid());
    }

    /**
     * @test
     */
    public function it_fails_validation_with_invalid_dates()
    {
        $user = User::factory()->create();
        $resume = Resume::factory()->for($user)->create();

        $experience = $resume->experiences()->create([
            'position' => 'Developer',
            'company_name' => 'TechCorp',
            'start_date' => now(),
            'end_date' => now()->subDays(10), // Дата закінчення раніше за дату початку
        ]);

        $this->assertFalse($experience->isValid());
    }
}
```

### Resume Controller Tests

```php
// tests/Feature/ResumeWizardControllerTest.php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeWizardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_lists_resumes_for_authenticated_user()
    {
        Resume::factory()->for($this->user)->count(3)->create();
        Resume::factory()->count(5)->create(); // Інші користувачі

        $response = $this->actingAs($this->user)
            ->getJson('/api/resumes');

        $response->assertStatus(200);
        $this->assertEquals(3, $response['count']);
    }

    /**
     * @test
     */
    public function it_creates_a_resume()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/resumes', [
                'title' => 'My First Resume',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('resumes', [
            'user_id' => $this->user->id,
            'title' => 'My First Resume',
            'status' => 'draft',
        ]);
    }

    /**
     * @test
     */
    public function it_updates_resume_personal_info()
    {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/resumes/{$resume->id}", [
                'personal_info' => [
                    'first_name' => 'Іван',
                    'last_name' => 'Петренко',
                ],
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Іван', $response['data']['personal_info']['first_name']);
    }

    /**
     * @test
     */
    public function it_sends_verification_code()
    {
        $resume = Resume::factory()->for($this->user)->create();

        \Mail::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/send-verification-code", [
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('email_verifications', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * @test
     */
    public function it_verifies_email_with_correct_code()
    {
        $resume = Resume::factory()->for($this->user)->create();

        // Спочатку надіслати код
        $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/send-verification-code", [
                'email' => 'test@example.com',
            ]);

        // Отримати код з БД
        $verification = \App\Models\EmailVerification::where('email', 'test@example.com')->first();

        // Верифікувати
        $response = $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/verify-email", [
                'email' => 'test@example.com',
                'code' => $verification->code,
            ]);

        $response->assertStatus(200);
        $this->assertNotNull($response['data']['personal_info']['email_verified_at']);
    }

    /**
     * @test
     */
    public function it_rejects_invalid_verification_code()
    {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/verify-email", [
                'email' => 'test@example.com',
                'code' => 'wrong_code',
            ]);

        $response->assertStatus(404); // Код не існує
    }

    /**
     * @test
     */
    public function it_adds_experience_to_resume()
    {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/experiences", [
                'position' => 'Senior Developer',
                'company_name' => 'TechCorp',
                'start_date' => '2020-01-15',
                'end_date' => '2023-06-30',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('experiences', [
            'resume_id' => $resume->id,
            'position' => 'Senior Developer',
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_more_than_5_experiences()
    {
        $resume = Resume::factory()->for($this->user)->create();

        // Додати 5 досвідів
        for ($i = 0; $i < 5; $i++) {
            $resume->experiences()->create([
                'position' => "Job $i",
                'company_name' => "Company $i",
                'start_date' => now()->subYears(5 - $i),
                'end_date' => now()->subYears(4 - $i),
            ]);
        }

        // Спроба додати 6-й досвід
        $response = $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/experiences", [
                'position' => 'Job 6',
                'company_name' => 'Company 6',
                'start_date' => now()->subYears(6),
                'end_date' => now()->subYears(5),
            ]);

        $response->assertStatus(422);
    }

    /**
     * @test
     */
    public function it_adds_skill_to_resume()
    {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/skills", [
                'skill_name' => 'Laravel',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('skills', [
            'resume_id' => $resume->id,
            'skill_name' => 'Laravel',
        ]);
    }

    /**
     * @test
     */
    public function it_publishes_resume()
    {
        $resume = Resume::factory()->for($this->user)->create([
            'personal_info' => [
                'first_name' => 'Іван',
                'last_name' => 'Петренко',
                'email' => 'ivan@example.com',
                'email_verified_at' => now(),
            ],
        ]);

        $resume->experiences()->create([
            'position' => 'Developer',
            'company_name' => 'TechCorp',
            'start_date' => now()->subYear(),
            'end_date' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/publish");

        $response->assertStatus(200);
        $this->assertEquals('published', $response['data']['status']);
    }

    /**
     * @test
     */
    public function it_prevents_publishing_incomplete_resume()
    {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/resumes/{$resume->id}/publish");

        $response->assertStatus(422);
    }

    /**
     * @test
     */
    public function it_returns_stepper_status()
    {
        $resume = Resume::factory()->for($this->user)->create([
            'personal_info' => [
                'first_name' => 'Іван',
                'last_name' => 'Петренко',
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/resumes/{$resume->id}/stepper-status");

        $response->assertStatus(200);
        $this->assertTrue($response['data']['personal_info']);
        $this->assertFalse($response['data']['email']);
    }
}
```

---

## 2. Livewire Component Tests

### ResumeWizard Component Tests

```php
// tests/Livewire/ResumeWizardTest.php

namespace Tests\Livewire;

use App\Livewire\ResumeWizard;
use App\Models\Resume;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Resume $resume;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->resume = Resume::factory()->for($this->user)->create();
    }

    /**
     * @test
     */
    public function it_renders_the_wizard()
    {
        Livewire::actingAs($this->user)
            ->test(ResumeWizard::class, ['resume' => $this->resume])
            ->assertStatus(200)
            ->assertSee('Конструктор резюме');
    }

    /**
     * @test
     */
    public function it_navigates_to_next_step()
    {
        Livewire::actingAs($this->user)
            ->test(ResumeWizard::class, ['resume' => $this->resume])
            ->set('formData.personal_info.first_name', 'Іван')
            ->set('formData.personal_info.last_name', 'Петренко')
            ->call('nextStep')
            ->assertSet('currentStep', 2);
    }

    /**
     * @test
     */
    public function it_prevents_next_step_without_required_fields()
    {
        Livewire::actingAs($this->user)
            ->test(ResumeWizard::class, ['resume' => $this->resume])
            ->call('nextStep')
            ->assertSet('currentStep', 1); // Still on step 1
    }

    /**
     * @test
     */
    public function it_navigates_to_specific_step()
    {
        Livewire::actingAs($this->user)
            ->test(ResumeWizard::class, ['resume' => $this->resume])
            ->call('goToStep', 4)
            ->assertSet('currentStep', 4);
    }

    /**
     * @test
     */
    public function it_publishes_resume()
    {
        $this->resume->updatePersonalInfo([
            'first_name' => 'Іван',
            'last_name' => 'Петренко',
            'email' => 'ivan@example.com',
            'email_verified_at' => now(),
        ]);

        $this->resume->experiences()->create([
            'position' => 'Developer',
            'company_name' => 'TechCorp',
            'start_date' => now()->subYear(),
            'end_date' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ResumeWizard::class, ['resume' => $this->resume])
            ->set('currentStep', 6)
            ->call('publishResume')
            ->assertDispatchedTo(ResumeWizard::class, 'resume-published');
    }
}
```

### CardStep Component Tests

```php
// tests/Livewire/ResumeSteps/CardStepTest.php

namespace Tests\Livewire\ResumeSteps;

use App\Livewire\ResumeSteps\CardStep;
use App\Models\Resume;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardStepTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Resume $resume;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->resume = Resume::factory()->for($this->user)->create();
    }

    /**
     * @test
     */
    public function it_renders_card_step()
    {
        Livewire::test(CardStep::class, ['resume' => $this->resume])
            ->assertStatus(200)
            ->assertSee('Ваша картка-візитка');
    }

    /**
     * @test
     */
    public function it_validates_first_name()
    {
        Livewire::test(CardStep::class, ['resume' => $this->resume])
            ->call('updateFirstName', '')
            ->assertSet('errors.first_name', 'Ім\'я обов\'язкове');
    }

    /**
     * @test
     */
    public function it_updates_first_name()
    {
        Livewire::test(CardStep::class, ['resume' => $this->resume])
            ->call('updateFirstName', 'Іван')
            ->assertSet('formData.personal_info.first_name', 'Іван');
    }

    /**
     * @test
     */
    public function it_toggles_privacy()
    {
        Livewire::test(CardStep::class, ['resume' => $this->resume])
            ->call('updatePrivacy', true)
            ->assertSet('formData.personal_info.privacy', true);
    }
}
```

---

## 3. Dusk E2E Tests

### Resume Creation E2E Test

```php
// tests/Browser/ResumeCreationTest.php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ResumeCreationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function it_creates_a_resume_through_ui()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => \Hash::make('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/resumes/create')
                ->assertSee('Конструктор резюме')

                // Step 1: Personal Info
                ->type('input[placeholder="Наприклад: Іван"]', 'Іван')
                ->type('input[placeholder="Наприклад: Петренко"]', 'Петренко')
                ->click('button:contains("Далі →")')
                ->waitForText('Верифікація email')

                // Step 2: Email Verification
                ->type('input[type="email"]', 'ivan@example.com')
                ->click('button:contains("Надіслати код")')
                ->waitForText('Код відправлено')

                // (В реальному тесті потрібно отримати код з БД)
                ->type('input[maxlength="6"]', '123456')
                ->click('button:contains("Підтвердити код")')
                ->waitForText('Email верифіковано')

                // Step 3: Experience
                ->click('button:contains("Далі →")')
                ->waitForText('Досвід роботи')
                ->type('input[placeholder="Наприклад: Senior Laravel Developer"]', 'Senior Developer')
                ->type('input[placeholder="Наприклад: TechCorp"]', 'TechCorp')
                ->type('input[type="date"]:nth-of-type(1)', '2020-01-15')
                ->type('input[type="date"]:nth-of-type(2)', '2023-06-30')
                ->click('button:contains("Додати")')
                ->waitForText('Senior Developer');
        });
    }

    /**
     * @test
     */
    public function it_validates_required_fields()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/resumes/create')

                // Спроба перейти без заповнення полів
                ->click('button:contains("Далі →")')

                // Повинні бачити помилки
                ->assertSee('Ім\'я обов\'язкове')
                ->assertSee('Прізвище обов\'язкове');
        });
    }
}
```

---

## 4. Database Factories

```php
// database/factories/ResumeFactory.php

namespace Database\Factories;

use App\Models\Resume;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResumeFactory extends Factory
{
    protected $model = Resume::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(),
            'status' => 'draft',
            'personal_info' => [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'email' => null,
                'email_verified_at' => null,
                'phone' => null,
                'privacy' => false,
                'transparency' => false,
            ],
            'location' => [
                'city' => null,
                'city_id' => null,
                'street' => null,
                'building' => null,
                'latitude' => null,
                'longitude' => null,
                'no_location_binding' => false,
            ],
            'notifications' => [
                'site' => true,
                'email' => false,
                'sms' => false,
                'telegram' => false,
                'viber' => false,
                'whatsapp' => false,
            ],
        ];
    }
}
```

---

## 5. Test Configuration

Додай до `phpunit.xml`:

```xml
<phpunit>
    <!-- ... existing config ... -->
    
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_DRIVER" value="log"/>
    </php>
    
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        
        <testsuite name="Livewire">
            <directory suffix="Test.php">./tests/Livewire</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## Команди для запуску тестів:

```bash
# Усі тести
php artisan test

# Лише Unit тести
php artisan test --testsuite=Unit

# Лише Feature тести
php artisan test --testsuite=Feature

# Лише Livewire тести
php artisan test --testsuite=Livewire

# Dusk тести
php artisan dusk

# З coverage звітом
php artisan test --coverage

# Конкретний тест
php artisan test tests/Feature/ResumeWizardControllerTest.php::it_creates_a_resume
```

---

## Best Practices для тестування:

1. **Використовуй factories** для створення тестових даних
2. **RefreshDatabase trait** для ізоляції тестів
3. **Обов'язкові тести**:
   - Валідація
   - Авторизація (користувач не може редагувати чужі резюме)
   - Граничні умови (max 5 досвідів, max 6-значний код)
   - Благополучні сценарії
   - Помилкові сценарії

4. **Dusk тести** для критичних user flows (створення, публікація, верифікація)

5. **Test naming**: `it_[описання що тестується]`

---

## Coverage Goals:

- **Models**: 80%+
- **Controllers**: 85%+
- **Components**: 70%+
- **Overall**: 75%+

---

Всі 6 промптів готові до використання! 🎉
