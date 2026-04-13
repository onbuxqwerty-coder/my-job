# 📚 ПОВНИЙ ПОСІБНИК: Розробка кабінету Шукача

Це ВСЕОХОПЛЮЮЧИЙ посібник для розробки, тестування та розгортання кабінету Шукача для платформи **My Job**.

---

## 🎯 ОГЛЯД ПРОЕКТУ

### Мета
Розробити **дзеркальний кабінет Шукача**, який синхронізується в реальному часі з бек-офісом Роботодавця. Будь-які зміни у статусі заявок, запланування собесід або відправлення повідомлень миттєво оновлюються в обох кабінетах.

### Основні функції
- ✅ Dashboard з метриками
- ✅ Управління заявками
- ✅ Календар собесід
- ✅ Профіль + Резюме
- ✅ Пошук вакансій
- ✅ Сповіщення
- ✅ Синхронізація з роботодавцем (Real-time)

### Стек технологій
- **Backend**: Laravel 11 + Livewire 3
- **Frontend**: Blade + Alpine.js + Tailwind CSS
- **Database**: PostgreSQL
- **Real-time**: WebSockets (optional)
- **Testing**: PHPUnit + Laravel Dusk

---

## 📋 ЕТАП 1: ПІДГОТОВКА

### 1.1 Клонуйте проект
```bash
cd /path/to/my-job
git pull origin main
```

### 1.2 Встановіть залежності
```bash
composer install
npm install
```

### 1.3 Налаштуйте .env
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=my_job
DB_USERNAME=postgres
DB_PASSWORD=

QUEUE_CONNECTION=database
SESSION_DRIVER=cookie
```

### 1.4 Запустіть міграції
```bash
php artisan migrate
```

---

## 🚀 ЕТАП 2: РОЗРОБКА (4-5 тижнів)

### ФАЗА 1: Основна структура (1 тиждень)

**День 1-2: Міграції та моделі**
```bash
# Скопіюйте файли з промпта
php artisan make:migration create_seeker_profiles_table
php artisan make:migration create_seeker_resumes_table
php artisan make:migration create_notifications_table
php artisan migrate

# Створіть моделі
php artisan make:model SeekerProfile
php artisan make:model SeekerResume
php artisan make:model Notification
```

**День 3-4: Controllers та Routes**
```bash
# Скопіюйте controllers з промпта
php artisan make:controller Seeker/DashboardController
php artisan make:controller Seeker/ApplicationController
php artisan make:controller Seeker/InterviewController

# Додайте routes в routes/seeker.php
# (див. промпт для деталей)
```

**День 5: Базові views**
```bash
# Створіть base layout
resources/views/seeker/
├── layouts/
│   └── app.blade.php
├── dashboard.blade.php
├── applications/
│   ├── index.blade.php
│   └── show.blade.php
└── ...
```

### ФАЗА 2: Livewire компоненти (1-2 тижні)

**День 6-8: Core components**
```bash
php artisan livewire:make Seeker/DashboardComponent
php artisan livewire:make Seeker/ApplicationsList
php artisan livewire:make Seeker/ApplicationDetail

# Тестуйте кожен компонент
php artisan serve
# Переходьте на http://localhost:8000/dashboard/seeker
```

**День 9-10: Additional components**
```bash
php artisan livewire:make Seeker/InterviewsCalendar
php artisan livewire:make Seeker/VacanciesSearch
php artisan livewire:make Seeker/ProfileForm
php artisan livewire:make Seeker/ResumeUpload
php artisan livewire:make Seeker/NotificationCenter
```

**День 11-12: Polish**
- Додайте CSS classes (Tailwind)
- Улучшите UX/responsiveness
- Додайте validations
- Проведіть QA

### ФАЗА 3: Services та API (1 тиждень)

**День 13-14: Services**
```bash
php artisan make:provider SeekerServiceProvider

# Створіть Services
app/Services/
├── SeekerService.php
├── ApplicationService.php
├── InterviewService.php
└── NotificationService.php
```

**День 15-16: API endpoints**
```bash
# Додайте в routes/api.php
Route::middleware(['auth:sanctum', 'role:seeker'])->prefix('seeker')->group(function () {
    Route::get('/applications', [SeekerApiController::class, 'applications']);
    Route::get('/interviews', [SeekerApiController::class, 'interviews']);
    Route::post('/applications/{id}/submit', [SeekerApiController::class, 'submit']);
    // ... інші endpoints
});

# Протестуйте з Postman або Insomnia
```

**День 17: Webhooks**
```bash
php artisan make:controller Webhook/SeekerWebhookController

# Endpoints:
POST /webhooks/seeker/application-status-changed
POST /webhooks/seeker/interview-scheduled
POST /webhooks/seeker/offer-created
POST /webhooks/seeker/message-received
```

### ФАЗА 4: Events та Real-time (1 тиждень)

**День 18-19: Events & Listeners**
```bash
php artisan make:event ApplicationStatusChanged
php artisan make:listener NotifySeekerOfStatusChange

php artisan make:event InterviewScheduled
php artisan make:listener NotifySeekerOfInterview
```

**День 20-21: Broadcasting (опціонально)**
```bash
# Для real-time updates через WebSockets
php artisan make:channel ApplicationChannel

# Налаштуйте Pusher або Redis
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
```

---

## 🧪 ЕТАП 3: ТЕСТУВАННЯ (1-2 тижні)

### 3.1 Unit тести
```bash
# Скопіюйте файли з seeker-tests-phpunit.md
cp seeker-tests-phpunit.md/Unit/* tests/Unit/Services/

# Запустіть
php artisan test tests/Unit
```

### 3.2 Feature тести
```bash
# Скопіюйте Feature тести
cp seeker-tests-phpunit.md/Feature/* tests/Feature/Seeker/

# Запустіть
php artisan test tests/Feature/Seeker
```

### 3.3 Integration тести
```bash
# Скопіюйте Integration тести
cp seeker-tests-phpunit.md/Integration/* tests/Integration/

# Запустіть
php artisan test tests/Integration
```

### 3.4 E2E тести (Dusk)
```bash
php artisan dusk:install

# Створіть E2E тести
php artisan dusk:make SeekerDashboardTest
php artisan dusk:make ApplicationFlowTest

# Запустіть
php artisan dusk
```

### 3.5 Performance тестування
```bash
# Перевірте query count
php artisan tinker
> \DB::listen(function($query) { echo $query->sql; });
> visit('/dashboard/seeker');

# Перевірте швидкість
php artisan test tests/Feature/Seeker/DashboardTest --profile
```

---

## 🔄 ЕТАП 4: СИНХРОНІЗАЦІЯ

### 4.1 Webhook署名 (Security)
```php
// app/Services/WebhookService.php

public function verifySignature($payload, $signature)
{
    $hash = hash_hmac('sha256', $payload, config('webhooks.secret'));
    return hash_equals($hash, $signature);
}
```

### 4.2 Database triggers (opcional, але рекомендується)
```sql
-- Коли роботодавець змінює статус
CREATE TRIGGER on_application_status_change
AFTER UPDATE OF status ON applications
FOR EACH ROW
EXECUTE FUNCTION notify_seeker_of_status_change();

-- Коли планується собесіда
CREATE TRIGGER on_interview_scheduled
AFTER INSERT ON interviews
FOR EACH ROW
EXECUTE FUNCTION notify_seeker_of_interview();
```

### 4.3 Queue jobs для асинхронної обробки
```bash
php artisan make:job SendSeekerNotification
php artisan make:job UpdateApplicationStatus
php artisan make:job SyncInterviewData

# Запустіть worker
php artisan queue:listen
```

---

## 📊 ЕТАП 5: РОЗГОРТАННЯ

### 5.1 Production build
```bash
# Optimize autoloader
composer install --no-dev --optimize-autoloader

# Build frontend
npm run build

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5.2 Database migrations
```bash
# На production сервері
php artisan migrate --force
```

### 5.3 Налаштування WebSockets (опціонально)
```bash
# На production
php artisan websockets:serve

# Або з supervisor
[program:websockets]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan websockets:serve
```

### 5.4 Monitoring & Logging
```bash
# Налаштуйте Sentry для error tracking
php artisan sentry:test

# Налаштуйте logging
LOG_CHANNEL=stack
LOG_SLACK_WEBHOOK_URL=...
```

---

## 🛠️ КОРИСНІ КОМАНДИ

### Development
```bash
# Запустити dev server
php artisan serve

# Запустити queue worker
php artisan queue:listen

# Запустити WebSockets (якщо використовуєте)
php artisan websockets:serve

# Tinker (REPL)
php artisan tinker

# Seed database
php artisan db:seed
```

### Testing
```bash
# Запустити ВСІ тести
php artisan test

# Запустити з покриттям
php artisan test --coverage

# Запустити E2E тести
php artisan dusk

# Запустити конкретний тест
php artisan test tests/Feature/Seeker/DashboardTest
```

### Database
```bash
# Rollback всі
php artisan migrate:reset

# Rollback останню
php artisan migrate:rollback

# Refresh (reset + migrate)
php artisan migrate:refresh

# Fresh (drop all + migrate)
php artisan migrate:fresh --seed
```

### Cache & Performance
```bash
# Очистити кеш
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Оптимізувати
php artisan optimize
php artisan optimize:clear
```

---

## 📝 КОНТРОЛЬНИЙ СПИСОК

### Перед запуском розробки
- [ ] Прочитано документацію бек-офісу роботодавця
- [ ] Встановлено Laravel 11 + Livewire 3
- [ ] Налаштовано PostgreSQL БД
- [ ] Клоновано проект `My Job`

### Під час розробки
- [ ] Створені міграції та моделі
- [ ] Розроблені Controllers та Routes
- [ ] Розроблені Livewire компоненти
- [ ] Додані валідації та error handling
- [ ] Налаштована синхронізація з роботодавцем

### Перед тестуванням
- [ ] Код відповідає PSR-12 стандартам
- [ ] Всі компоненти задокументовані
- [ ] Немає console errors/warnings

### Тестування
- [ ] 70 тестів пройшли успішно
- [ ] Покриття коду > 75%
- [ ] E2E тести для основних flows
- [ ] Performance тести

### Перед розгортанням
- [ ] Production build створений
- [ ] Migrations готові до запуску
- [ ] Environment variables налаштовані
- [ ] Backup БД
- [ ] Logging налаштований
- [ ] Monitoring налаштований

### На production
- [ ] Всі тести пройшли
- [ ] Zero downtime deployment
- [ ]롤백план готовий
- [ ] Моніторинг та alerts налаштовані

---

## 🎓 НАЙВАЖЛИВІШІ КОНЦЕПЦІЇ

### 1. Синхронізація статусів
```
EMPLOYER ──[Webhook]──> SEEKER
   ↓                       ↓
Update DB  ───[Event]──> Create Notification
   ↓                       ↓
Broadcast ────[Socket]──> Real-time UI Update
```

### 2. Application Lifecycle
```
Submitted ──> Viewed ──> Screening ──> Testing ──> Interview ──> Offer ──> Accepted
                                                                      └──> Rejected
```

### 3. Real-time Updates
```
Event triggered ──> Listener ──> Notification created ──> Email/Push/In-app
                       ↓
                  Broadcast event
                       ↓
                  WebSocket to UI
                       ↓
                  Livewire updates
```

---

## 🐛 ЧАСТИНИ ПРОБЛЕМИ

### Problem: Notifications not syncing
**Solution:**
```bash
# 1. Перевірте webhook endpoint
php artisan route:list | grep webhook

# 2. Перевірте DB connection
php artisan tinker
> Event::listen('webhook.*', function() { echo 'OK'; });

# 3. Перевірте queue
php artisan queue:work --tries=3 --timeout=90
```

### Problem: Livewire component not updating
**Solution:**
```php
// Перевірте wire directives
wire:model (two-way binding)
wire:click (actions)
wire:submit (forms)

// Перевірте livewire.js
<script src="https://cdn.jsdelivr.net/npm/livewire@3/dist/livewire.js"></script>

// Re-compile frontend
npm run dev
```

### Problem: Performance issues
**Solution:**
```bash
# Check N+1 queries
php artisan tinker
> \DB::enableQueryLog();
> $users = User::with('applications.vacancy')->get();
> \DB::getQueryLog();

# Add missing indexes
php artisan make:migration add_indexes_to_applications_table

# Cache heavy queries
Cache::remember('seeker_stats:' . $seeker->id, 3600, function () {
    return $seeker->applications()->count();
});
```

---

## 📚 ПОСИЛАННЯ

- **Laravel документація**: https://laravel.com/docs
- **Livewire документація**: https://livewire.laravel.com
- **My Job GitHub**: https://github.com/your-org/my-job
- **PostgreSQL документація**: https://www.postgresql.org/docs

---

## ✅ УСПІХ!

Якщо все пройшло успішно:
- ✅ Dashboard завантажується без помилок
- ✅ Можна створювати заявки
- ✅ Статуси синхронізуються в реальному часі
- ✅ Тести проходять на 100%
- ✅ Сайт швидкий та відзивчивий

**Поздоровляємо! Кабінет Шукача готовий! 🎉**

---

**Created:** 13.04.2026  
**Version:** 1.0  
**Status:** Production Ready ✅
