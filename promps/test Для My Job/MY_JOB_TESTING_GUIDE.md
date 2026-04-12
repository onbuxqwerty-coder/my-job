# 🚀 Як запустити адаптовані тести в My Job проекті

## 📋 Огляд

Я адаптував всі тести **спеціально для вашої My Job архітектури** (Laravel 13 + Livewire + Filament).

Файл: **ADAPTED_TESTS_FOR_MY_JOB.md** містить готові тести для:
- ✅ Employer Dashboard (Кабінет роботодавця)
- ✅ Vacancy Management (Управління вакансіями)
- ✅ Applicants Management (Управління заявками)
- ✅ Livewire Components
- ✅ Telegram Bot Integration
- ✅ Stripe Payment Integration
- ✅ E2E тести (Dusk)

---

## 🔧 ВСТАНОВЛЕННЯ

### Крок 1: Установити тестові залежності

```bash
# Перейти в папку my-job
cd my-job

# Встановити PHPUnit, Livewire Testing, Dusk
composer require --dev phpunit/phpunit laravel/dusk laravel/browser-kit-testing
```

### Крок 2: Встановити Dusk браузери

```bash
# Для E2E тестів (Selenium + Chrome)
php artisan dusk:install
```

### Крок 3: Встановити додаткові залежності

```bash
# Для Livewire тестування
composer require --dev livewire/livewire

# Для Telegram тестів (якщо не встановлено)
composer require nutgram/nutgram
```

---

## 📁 ДЕ ЗБЕРЕГТИ ТЕСТИ

### Структура папок у вашому проекті:

```
my-job/
├── tests/
│   ├── Feature/
│   │   ├── Employer/
│   │   │   ├── EmployerDashboardTest.php          ← скопіюйте
│   │   │   ├── VacancyManagementTest.php          ← скопіюйте
│   │   │   ├── ApplicantsManagementTest.php       ← скопіюйте
│   │   │   └── CandidateDetailTest.php            ← скопіюйте
│   │   │
│   │   ├── Livewire/
│   │   │   └── EmployerApplicantsTest.php         ← скопіюйте
│   │   │
│   │   └── Auth/
│   │       └── AuthenticationTest.php             ← новий
│   │
│   ├── Unit/
│   │   └── Services/
│   │       ├── VacancyServiceTest.php             ← скопіюйте
│   │       └── ApplicationServiceTest.php         ← скопіюйте
│   │
│   ├── Integration/
│   │   ├── TelegramBotTest.php                    ← скопіюйте
│   │   └── StripePaymentTest.php                  ← скопіюйте
│   │
│   ├── Browser/
│   │   └── EmployerWorkflowTest.php               ← скопіюйте (Dusk)
│   │
│   └── TestCase.php                              ← вже існує
│
└── phpunit.xml                                   ← вже існує
```

### Як скопіювати файли:

```bash
# 1. Відкрийте ADAPTED_TESTS_FOR_MY_JOB.md
# 2. Знайдіть розділ "Файл: tests/Feature/Employer/EmployerDashboardTest.php"
# 3. Скопіюйте весь PHP код
# 4. У вашому IDE:
#    - Клік правою на: tests/Feature/Employer/
#    - New File → EmployerDashboardTest.php
#    - Вставити код (Ctrl+V)
#    - Зберегти

# 5. Повторіть для всіх файлів тестів
```

---

## 🖥️ ЗАПУСК ТЕСТІВ

### Команда 1: Все тести

```bash
php artisan test

# Результат:
# PASS  Tests/Feature/Employer/EmployerDashboardTest
#   ✓ employer_can_view_dashboard
#   ✓ dashboard_shows_vacancy_statistics
#   ✓ dashboard_shows_application_count
# 
# PASS  Tests/Feature/Employer/VacancyManagementTest
#   ✓ employer_can_create_vacancy
#   ✓ employer_can_edit_vacancy
# 
# ✓ 37 tests passed
```

### Команда 2: Окремі категорії

```bash
# 🧪 Unit тести (Service classes)
php artisan test tests/Unit

# 🔗 Feature тести (Livewire компоненти)
php artisan test tests/Feature

# 🌐 Integration тести (Telegram, Stripe)
php artisan test tests/Integration

# 🎬 E2E тести (Dusk)
php artisan dusk
```

### Команда 3: Конкретна категорія

```bash
# Тільки тести роботодавця
php artisan test tests/Feature/Employer

# Тільки Livewire тести
php artisan test tests/Feature/Livewire

# Тільки Telegram тести
php artisan test tests/Integration/TelegramBotTest

# Тільки Stripe тести
php artisan test tests/Integration/StripePaymentTest
```

### Команда 4: Покриття кодом

```bash
# Показати покриття кодом (коли тести пройшли)
php artisan test --coverage

# З більш детальним звітом
php artisan test --coverage --coverage-html coverage/

# Переглянути HTML звіт
open coverage/index.html  # або правий клік → Open in Browser
```

### Команда 5: Watch режим (розробка)

```bash
# Автоматичний перезапуск тестів при змінах
php artisan test --watch

# Або з конкретного файлу
php artisan test tests/Feature/Employer/VacancyManagementTest.php --watch
```

### Команда 6: Verbose режим (деталі)

```bash
# Показати детальний вивід
php artisan test --verbose

# З namen тестів
php artisan test --testdox
```

---

## 🔧 КОНФІГ (phpunit.xml)

Найважливіші параметри для My Job:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnRisky="true"
         failOnWarning="true">
    <testsuites>
        <!-- Unit тести -->
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>

        <!-- Feature тести -->
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>

        <!-- Integration тести -->
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
    </testsuites>

    <!-- Environment для тестування -->
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

---

## 🗂️ .env.testing (для тестів)

Створіть файл `.env.testing`:

```bash
APP_ENV=testing
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

MAIL_DRIVER=log

QUEUE_DRIVER=sync

STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...

TELEGRAM_TOKEN=your_test_token
```

---

## 🆘 ТИПОВІ ПОМИЛКИ

### ❌ Помилка: "Call to undefined method"

```
FatalThrowableError: Call to undefined method App\Models\Vacancy::getStatistics()
```

**Причина:** Метод не існує в моделі

**Рішення:** Додайте метод в модель `Vacancy.php` або в `VacancyService.php`

```php
// У app/Services/VacancyService.php
public function getStatistics($vacancyId)
{
    $vacancy = Vacancy::find($vacancyId);
    return [
        'total' => $vacancy->applications()->count(),
        'pending' => $vacancy->applications()->where('status', 'pending')->count(),
        // ...
    ];
}
```

### ❌ Помилка: "Table 'vacancies' doesn't exist"

```
SQLSTATE[HY000]: General error: 1 no such table: vacancies
```

**Причина:** Миграції не запущені для тестової БД

**Рішення:** Перед тестами запустіть:

```bash
php artisan migrate --env=testing

# Або додайте в TestCase.php:
protected function setUp(): void
{
    parent::setUp();
    $this->artisan('migrate', ['--env' => 'testing']);
}
```

### ❌ Помилка: "Undefined property: $employer->role"

```
ErrorException: Undefined property
```

**Причина:** Властивість не міститься в моделі

**Рішення:** Проверіть БД схему та додайте властивість в `User.php`:

```php
class User extends Model
{
    protected $fillable = ['name', 'email', 'role', ...];
    
    // Або додайте в migration:
    // $table->enum('role', ['candidate', 'employer', 'admin']);
}
```

### ❌ Помилка: "Livewire component not found"

```
ComponentNotFoundException: Could not find component [employer.applicants]
```

**Причина:** Компонент не зареєстрований

**Рішення:** Перевірте що компонент існує:
```
app/Livewire/Employer/Applicants.php
```

---

## ✅ РЕКОМЕНДОВАНИЙ ПОРЯДОК ЗАПУСКУ

```bash
# 1. Setup (один раз)
composer install
php artisan dusk:install

# 2. Перед кожним запуском тестів
php artisan migrate:fresh --env=testing

# 3. Запустіть всі тести
php artisan test

# 4. Якщо щось не пройшло - debug
php artisan test --verbose

# 5. Покриття кодом
php artisan test --coverage
```

---

## 📊 ОЧІКУВАНІ РЕЗУЛЬТАТИ

Коли запустите `php artisan test`, повинні побачити:

```
PASS  Tests/Feature/Employer/EmployerDashboardTest
  ✓ employer_can_view_dashboard
  ✓ dashboard_shows_vacancy_statistics
  ✓ dashboard_shows_application_count
  ✓ dashboard_shows_application_trends
  ✓ only_employer_can_access_dashboard
  ✓ unauthenticated_user_redirected_to_login

PASS  Tests/Feature/Employer/VacancyManagementTest
  ✓ employer_can_create_vacancy
  ✓ employer_can_edit_vacancy
  ✓ employer_can_delete_vacancy
  ✓ employer_cannot_edit_other_employer_vacancy
  ✓ employer_can_view_own_vacancies
  ✓ employer_can_feature_vacancy

PASS  Tests/Feature/Employer/ApplicantsManagementTest
  ✓ employer_can_view_applicants
  ✓ employer_can_change_application_status
  ✓ employer_can_add_note_to_application
  ✓ employer_can_rate_application
  ✓ employer_can_filter_applications_by_status
  ✓ employer_can_search_applicants
  ✓ employer_cannot_see_applications_to_other_vacancies

... (та так далі)

37 passed (1.23s)
```

---

## 🎯 НАСТУПНІ КРОКИ

1. ✅ Скопіюйте тести з файлу `ADAPTED_TESTS_FOR_MY_JOB.md`
2. ✅ Створіть структуру папок `tests/Feature/Employer/` тощо
3. ✅ Вставте PHP код в нові файли
4. ✅ Запустіть: `php artisan test`
5. ✅ Виправте помилки (див. розділ "Типові помилки")
6. ✅ Достигніть 80%+ покриття кодом

---

## 💡 КОРИСНІ КОМАНДИ

```bash
# Виконати один тест
php artisan test tests/Feature/Employer/EmployerDashboardTest.php::test_employer_can_view_dashboard

# Зупинити після першої помилки
php artisan test --stop-on-failure

# Список всіх тестів без запуску
php artisan test --list

# Паралельний запуск (швидше)
php artisan test --parallel --processes=4

# Профайлінг (який тест найповільніший)
php artisan test --profile
```

---

**Готово! Теперь у вас є тести спеціально для My Job проекту! 🚀**

Прочитайте **ADAPTED_TESTS_FOR_MY_JOB.md** для повного коду всіх тестів.
