# Project Structure — My Job

> Laravel 13 · Livewire/Volt · Filament · Telegram · Stripe

```
my-job/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CleanupTempUploads.php          # Видаляє тимчасові завантажені файли
│   │       ├── DeactivateExpiredFeatured.php    # Деактивує прострочені featured-вакансії
│   │       └── SendVacancyAlerts.php            # Розсилає алерти підписникам Telegram
│   │
│   ├── DTOs/
│   │   ├── ApplyDTO.php                        # Дані для подачі заявки
│   │   └── VacancySearchDTO.php                # Фільтри пошуку вакансій
│   │
│   ├── Enums/
│   │   ├── ApplicationStatus.php               # pending | screening | interview | hired | rejected
│   │   ├── EmploymentType.php                  # full_time | part_time | remote | freelance
│   │   ├── InterviewStatus.php                 # scheduled | completed | cancelled
│   │   ├── InterviewType.php                   # online | offline
│   │   ├── MessageType.php                     # Тип повідомлення роботодавця
│   │   └── UserRole.php                        # candidate | employer | admin
│   │
│   ├── Filament/
│   │   └── Resources/
│   │       ├── Categories/
│   │       │   ├── CategoryResource.php
│   │       │   ├── Pages/  (Create · Edit · List)
│   │       │   ├── Schemas/CategoryForm.php
│   │       │   └── Tables/CategoriesTable.php
│   │       ├── Companies/
│   │       │   ├── CompanyResource.php
│   │       │   ├── Pages/  (Create · Edit · List)
│   │       │   ├── Schemas/CompanyForm.php
│   │       │   └── Tables/CompaniesTable.php
│   │       ├── Users/
│   │       │   ├── UserResource.php
│   │       │   ├── Pages/  (Create · Edit · List)
│   │       │   ├── Schemas/UserForm.php
│   │       │   └── Tables/UsersTable.php
│   │       └── Vacancies/
│   │           ├── VacancyResource.php
│   │           ├── Pages/  (Create · Edit · List)
│   │           ├── Schemas/VacancyForm.php
│   │           └── Tables/VacanciesTable.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/VerifyEmailController.php
│   │   │   ├── Controller.php                  # Base controller
│   │   │   └── StripeWebhookController.php     # Обробка Stripe webhook
│   │   └── Middleware/
│   │       └── EnsureUserRole.php              # Перевірка ролі (candidate/employer/admin)
│   │
│   ├── Jobs/
│   │   ├── DeactivateExpiredFeaturedVacancies.php
│   │   ├── SendApplicationStatusNotification.php
│   │   ├── SendInterviewReminder.php
│   │   └── SendNewApplicationNotification.php
│   │
│   ├── Livewire/
│   │   ├── Actions/Logout.php
│   │   └── Forms/LoginForm.php
│   │
│   ├── Mail/
│   │   ├── CandidateMessageMail.php            # Лист кандидату від роботодавця
│   │   ├── InterviewInvitationMail.php
│   │   └── InterviewReminderMail.php
│   │
│   ├── Models/
│   │   ├── Application.php                     # Заявка кандидата на вакансію
│   │   ├── ApplicationNote.php                 # Нотатки HR по заявці
│   │   ├── CandidateMessage.php                # Повідомлення роботодавця кандидату
│   │   ├── Category.php                        # Категорія вакансій (з підкатегоріями)
│   │   ├── Company.php                         # Компанія-роботодавець
│   │   ├── Interview.php                       # Інтерв'ю (запланований/завершений)
│   │   ├── MessageTemplate.php                 # Шаблони повідомлень HR
│   │   ├── TelegramSubscription.php            # Telegram-підписка на категорію
│   │   ├── User.php                            # Користувач (candidate | employer | admin)
│   │   └── Vacancy.php                         # Вакансія
│   │
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── Filament/AdminPanelProvider.php     # Конфігурація Filament Admin
│   │   ├── TelegramServiceProvider.php
│   │   └── VoltServiceProvider.php
│   │
│   ├── Services/
│   │   ├── ApplicationService.php              # Логіка подачі / обробки заявок
│   │   ├── CommunicationService.php            # Повідомлення та листи кандидатам
│   │   ├── InterviewService.php                # Планування та управління інтерв'ю
│   │   ├── PaymentService.php                  # Stripe: featured-розміщення вакансій
│   │   ├── PhoneOtpService.php                 # OTP-верифікація телефону
│   │   ├── SeoService.php                      # Meta-теги / sitemap
│   │   ├── TelegramService.php                 # Відправка повідомлень через Telegram API
│   │   └── VacancyService.php                  # CRUD + пошук вакансій
│   │
│   ├── Telegram/
│   │   ├── Callbacks/
│   │   │   └── AlertToggleCallback.php         # Вмикає/вимикає Telegram-алерти
│   │   └── Commands/
│   │       ├── AlertsCommand.php               # /alerts — управління підписками
│   │       └── StartCommand.php                # /start — реєстрація через бот
│   │
│   └── View/
│       └── Components/
│           ├── AppLayout.php                   # Layout для авторизованих сторінок
│           └── GuestLayout.php                 # Layout для публічних сторінок
│
├── config/
│   ├── app.php · auth.php · cache.php · database.php
│   ├── filesystems.php · logging.php · mail.php
│   ├── queue.php · services.php · session.php
│   └── telegram.php                            # Конфіг Nutgram / webhook
│
├── database/
│   ├── factories/
│   │   ├── ApplicationFactory.php
│   │   ├── CategoryFactory.php
│   │   ├── CompanyFactory.php
│   │   ├── UserFactory.php
│   │   └── VacancyFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2025_01_01_000010_create_categories_table.php
│   │   ├── 2025_01_01_000020_create_companies_table.php
│   │   ├── 2025_01_01_000030_create_vacancies_table.php
│   │   ├── 2025_01_01_000040_create_applications_table.php
│   │   ├── 2025_01_01_000050_add_telegram_fields_to_users_table.php
│   │   ├── 2025_01_01_000060_update_schema_missing_fields.php
│   │   ├── 2025_01_01_000070_add_featured_to_vacancies.php
│   │   ├── 2025_01_01_000080_create_telegram_subscriptions_table.php
│   │   ├── 2025_01_01_000090_add_phone_to_users_table.php
│   │   ├── 2025_01_01_000100_add_parent_id_to_categories_table.php
│   │   ├── 2026_04_09_*_add_notes_to_applications_table.php
│   │   ├── 2026_04_09_*_add_rating_to_applications_table.php
│   │   ├── 2026_04_09_*_create_application_notes_table.php
│   │   ├── 2026_04_09_*_create_message_templates_table.php
│   │   ├── 2026_04_09_*_create_candidate_messages_table.php
│   │   └── 2026_04_09_*_create_interviews_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
│
├── deploy/
│   ├── DEPLOY.md                               # Інструкція деплою
│   ├── nginx.conf                              # Nginx конфігурація
│   └── supervisor.conf                         # Supervisor для queue worker
│
├── docs/
│   ├── Brandboock  My Job.docx
│   └── PROJECT_STRUCTURE.md                    # ← цей файл
│
├── promps/                                     # Архів промптів розробки
│   ├── prompt_01_init.md
│   ├── prompt_02_Database Seeding and Main Search Interface.md
│   ├── prompt_03_telegram_applications.md
│   ├── prompt_04_Employer Dashboard & Admin Panel.md
│   ├── prompt_05_monetization_production.md
│   ├── modular_prompt.md
│   ├── job-search-redesign-prompt.md
│   └── claude-code-job-redesign-prompt.md
│
├── resources/
│   ├── css/app.css
│   ├── js/
│   │   ├── app.js
│   │   └── bootstrap.js
│   └── views/
│       ├── components/                         # Blade UI-компоненти (кнопки, інпути, nav)
│       │   ├── action-message.blade.php
│       │   ├── application-logo.blade.php
│       │   ├── auth-session-status.blade.php
│       │   ├── danger-button.blade.php
│       │   ├── dropdown.blade.php
│       │   ├── dropdown-link.blade.php
│       │   ├── footer.blade.php
│       │   ├── header.blade.php
│       │   ├── input-error.blade.php
│       │   ├── input-label.blade.php
│       │   ├── modal.blade.php
│       │   ├── navigation.blade.php
│       │   ├── nav-link.blade.php
│       │   ├── primary-button.blade.php
│       │   ├── responsive-nav-link.blade.php
│       │   ├── secondary-button.blade.php
│       │   └── text-input.blade.php
│       ├── emails/                             # Email-шаблони
│       │   ├── candidate-message.blade.php
│       │   ├── interview-invitation.blade.php
│       │   └── interview-reminder.blade.php
│       ├── interview/
│       │   └── response.blade.php              # Підтвердження/відхилення інтерв'ю
│       ├── layouts/
│       │   ├── app.blade.php
│       │   └── guest.blade.php
│       ├── livewire/
│       │   ├── layout/navigation.blade.php
│       │   ├── pages/
│       │   │   ├── auth/                       # login · register · forgot-password · ...
│       │   │   ├── employer/
│       │   │   │   ├── analytics.blade.php     # Статистика вакансій
│       │   │   │   ├── applicants.blade.php    # Список заявок
│       │   │   │   ├── candidate-detail.blade.php
│       │   │   │   ├── candidates.blade.php    # База кандидатів
│       │   │   │   ├── dashboard.blade.php     # Кабінет роботодавця
│       │   │   │   ├── message-templates.blade.php
│       │   │   │   ├── profile.blade.php
│       │   │   │   └── vacancies/edit.blade.php
│       │   │   └── jobs/
│       │   │       ├── _filters.blade.php      # Partial: фільтри пошуку
│       │   │       ├── index.blade.php         # Головна сторінка пошуку
│       │   │       └── show.blade.php          # Сторінка вакансії
│       │   ├── profile/                        # Форми профілю (пароль, дані, видалення)
│       │   └── welcome/navigation.blade.php
│       ├── payment/
│       │   ├── cancel.blade.php
│       │   └── success.blade.php
│       ├── dashboard.blade.php
│       ├── profile.blade.php
│       ├── sitemap.blade.php                   # XML sitemap
│       └── welcome.blade.php
│
├── routes/
│   ├── auth.php                                # Маршрути авторизації (Breeze)
│   ├── console.php                             # Artisan-команди
│   ├── telegram.php                            # Webhook Telegram (Nutgram)
│   └── web.php                                 # Основні маршрути
│
├── .env / .env.example
├── artisan
├── CLAUDE.md                                   # AI-інструкції для розробки
├── composer.json
├── package.json
├── phpunit.xml
└── postcss.config.js
```

---

## Ключові модулі

| Шар | Технологія | Призначення |
|-----|-----------|-------------|
| Backend | Laravel 13 | Core framework |
| UI | Livewire 3 / Volt | Реактивні компоненти без JS |
| Admin | Filament 3 | Адмін-панель (users, vacancies, companies, categories) |
| Telegram | Nutgram | Webhook-бот, алерти, підписки |
| Payments | Stripe | Featured-розміщення вакансій |
| Queue | Redis + Laravel Queues | Async: email, Telegram, нагадування |
| DB | MySQL / SQLite (dev) | Основна база даних |

---

## Структура бази даних

```
users ──────────┬── companies ── vacancies ── applications ── application_notes
                │                    │               │
                └── telegram_         └── telegram_   └── candidate_messages
                    subscriptions         (featured)       interviews
                                                           message_templates
```
