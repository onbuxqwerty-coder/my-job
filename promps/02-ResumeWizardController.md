# 02 — ResumeWizardController & API Endpoints

## Objetivo
Створити контролер з REST API endpoints для Resume Builder:
- GET `/api/resumes` — список резюме користувача
- POST `/api/resumes` — створення нового draft резюме
- GET `/api/resumes/{id}` — отримати резюме
- PATCH `/api/resumes/{id}` — оновити частину резюме (debounce)
- POST `/api/resumes/{id}/verify-email` — надіслати код на email
- POST `/api/resumes/{id}/confirm-email` — верифікувати код
- POST `/api/resumes/{id}/publish` — опублікувати резюме
- DELETE `/api/resumes/{id}` — видалити draft резюме

---

## Routes (api.php)

```php
// routes/api.php

use App\Http\Controllers\ResumeWizardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Resume list & create
    Route::get('/resumes', [ResumeWizardController::class, 'index']);
    Route::post('/resumes', [ResumeWizardController::class, 'store']);

    // Resume CRUD
    Route::get('/resumes/{resume}', [ResumeWizardController::class, 'show']);
    Route::patch('/resumes/{resume}', [ResumeWizardController::class, 'update']);
    Route::delete('/resumes/{resume}', [ResumeWizardController::class, 'destroy']);

    // Email verification
    Route::post('/resumes/{resume}/send-verification-code', [ResumeWizardController::class, 'sendVerificationCode']);
    Route::post('/resumes/{resume}/verify-email', [ResumeWizardController::class, 'verifyEmail']);

    // Experience management
    Route::post('/resumes/{resume}/experiences', [ResumeWizardController::class, 'storeExperience']);
    Route::patch('/resumes/{resume}/experiences/{experience}', [ResumeWizardController::class, 'updateExperience']);
    Route::delete('/resumes/{resume}/experiences/{experience}', [ResumeWizardController::class, 'destroyExperience']);

    // Skills management
    Route::post('/resumes/{resume}/skills', [ResumeWizardController::class, 'storeSkill']);
    Route::delete('/resumes/{resume}/skills/{skill}', [ResumeWizardController::class, 'destroySkill']);

    // Publish
    Route::post('/resumes/{resume}/publish', [ResumeWizardController::class, 'publish']);

    // Get stepper status
    Route::get('/resumes/{resume}/stepper-status', [ResumeWizardController::class, 'stepperStatus']);
});
```

---

## Controller

```php
// app/Http/Controllers/ResumeWizardController.php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\EmailVerification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;

class ResumeWizardController extends Controller
{
    /**
     * GET /api/resumes
     * Отримати всі резюме користувача (draft + published)
     */
    public function index(Request $request): JsonResponse
    {
        $resumes = Resume::forUser($request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($resume) => $this->formatResume($resume));

        return response()->json([
            'data' => $resumes,
            'count' => $resumes->count(),
        ]);
    }

    /**
     * POST /api/resumes
     * Створити нове draft резюме
     */
    public function store(Request $request): JsonResponse
    {
        $resume = Resume::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title', 'Нове резюме'),
            'status' => 'draft',
        ]);

        return response()->json([
            'data' => $this->formatResume($resume),
            'message' => 'Резюме створено',
        ], 201);
    }

    /**
     * GET /api/resumes/{id}
     * Отримати резюме з усіма пов'язаними даними
     */
    public function show(Resume $resume): JsonResponse
    {
        $this->authorize('view', $resume);

        return response()->json([
            'data' => $this->formatResumeDetailed($resume),
        ]);
    }

    /**
     * PATCH /api/resumes/{id}
     * Оновити частину резюме (debounce-friendly)
     * 
     * Приклади payload:
     * { "personal_info": { "first_name": "Іван" } }
     * { "location": { "city": "Київ", "city_id": 1 } }
     * { "notifications": { "email": true } }
     */
    public function update(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        // Визначити, які секції оновлюються
        $input = $request->only([
            'personal_info',
            'location',
            'notifications',
            'additional_info',
            'title',
        ]);

        try {
            // Оновити особисту інформацію
            if (isset($input['personal_info'])) {
                $this->validatePersonalInfo($input['personal_info']);
                $resume->updatePersonalInfo($input['personal_info']);
            }

            // Оновити локацію
            if (isset($input['location'])) {
                $this->validateLocation($input['location']);
                $resume->updateLocation($input['location']);
            }

            // Оновити сповіщення
            if (isset($input['notifications'])) {
                $resume->updateNotifications($input['notifications']);
            }

            // Оновити додаткову інформацію
            if (isset($input['additional_info'])) {
                $resume->updateAdditionalInfo($input['additional_info']);
            }

            // Оновити заголовок резюме
            if (isset($input['title'])) {
                $resume->update(['title' => $input['title']]);
            }

            $resume->refresh();

            return response()->json([
                'data' => $this->formatResumeDetailed($resume),
                'saved_at' => $resume->last_saved_at,
                'message' => 'Збережено',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Помилка при збереженні: ' . $e->getMessage(),
                'field' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * DELETE /api/resumes/{id}
     * Видалити draft резюме
     */
    public function destroy(Resume $resume): JsonResponse
    {
        $this->authorize('delete', $resume);

        if ($resume->status === 'published') {
            return response()->json([
                'error' => 'Неможливо видалити опубліковане резюме',
            ], 403);
        }

        $resume->delete();

        return response()->json([
            'message' => 'Резюме видалено',
        ]);
    }

    /**
     * POST /api/resumes/{id}/send-verification-code
     * Надіслати код верифікації на email
     */
    public function sendVerificationCode(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');

        // Знайти або створити запис про верифікацію
        $verification = EmailVerification::updateOrCreate(
            ['email' => $email],
            [
                'code' => EmailVerification::generateCode(),
                'code_expires_at' => now()->addMinutes(10),
                'is_verified' => false,
                'verified_at' => null,
            ]
        );

        // Надіслати код на email (Mailable)
        try {
            \Mail::to($email)->send(new \App\Mail\VerificationCodeMail($verification->code));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Помилка при надіслані email',
            ], 500);
        }

        return response()->json([
            'message' => 'Код верифікації надісланий на ' . $email,
            'code_expires_at' => $verification->code_expires_at,
        ]);
    }

    /**
     * POST /api/resumes/{id}/verify-email
     * Верифікувати email за кодом
     */
    public function verifyEmail(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $email = $request->input('email');
        $code = $request->input('code');

        $verification = EmailVerification::where('email', $email)->first();

        if (!$verification) {
            return response()->json([
                'error' => 'Код верифікації не знайдено',
            ], 404);
        }

        if (!$verification->verifyCode($code)) {
            return response()->json([
                'error' => 'Невірний код або код скінчився',
            ], 422);
        }

        // Оновити email в резюме
        $resume->updatePersonalInfo([
            'email' => $email,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'data' => $this->formatResumeDetailed($resume),
            'message' => 'Email верифіковано',
        ]);
    }

    /**
     * POST /api/resumes/{id}/experiences
     * Додати досвід роботи
     */
    public function storeExperience(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        // Перевірити, чи не перевищено максимум 5 досвідів
        if ($resume->experiences()->count() >= 5) {
            return response()->json([
                'error' => 'Максимум 5 записів про досвід роботи',
            ], 422);
        }

        $validated = $request->validate([
            'position' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'company_industry' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        $experience = $resume->experiences()->create($validated);

        return response()->json([
            'data' => $experience,
            'message' => 'Досвід додано',
        ], 201);
    }

    /**
     * PATCH /api/resumes/{id}/experiences/{experienceId}
     * Оновити досвід роботи
     */
    public function updateExperience(Request $request, Resume $resume, Experience $experience): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($experience->resume_id !== $resume->id) {
            return response()->json([
                'error' => 'Досвід не належить цьому резюме',
            ], 403);
        }

        $validated = $request->validate([
            'position' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_industry' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        $experience->update($validated);

        return response()->json([
            'data' => $experience,
            'message' => 'Досвід оновлено',
        ]);
    }

    /**
     * DELETE /api/resumes/{id}/experiences/{experienceId}
     */
    public function destroyExperience(Resume $resume, Experience $experience): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($experience->resume_id !== $resume->id) {
            return response()->json([
                'error' => 'Досвід не належить цьому резюме',
            ], 403);
        }

        $experience->delete();

        return response()->json([
            'message' => 'Досвід видалено',
        ]);
    }

    /**
     * POST /api/resumes/{id}/skills
     * Додати навичку
     */
    public function storeSkill(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'skill_name' => 'required|string|max:255',
        ]);

        $skill = $resume->skills()->create($validated);

        return response()->json([
            'data' => $skill,
            'message' => 'Навичка додана',
        ], 201);
    }

    /**
     * DELETE /api/resumes/{id}/skills/{skillId}
     */
    public function destroySkill(Resume $resume, Skill $skill): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($skill->resume_id !== $resume->id) {
            return response()->json([
                'error' => 'Навичка не належить цьому резюме',
            ], 403);
        }

        $skill->delete();

        return response()->json([
            'message' => 'Навичка видалена',
        ]);
    }

    /**
     * POST /api/resumes/{id}/publish
     * Опублікувати резюме
     */
    public function publish(Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        if (!$resume->isPublishable()) {
            $stepperStatus = $resume->getStepperStatus();
            
            return response()->json([
                'error' => 'Неможливо опублікувати резюме. Будь ласка, заповніть критичні поля.',
                'stepper_status' => $stepperStatus,
            ], 422);
        }

        $resume->update(['status' => 'published']);

        return response()->json([
            'data' => $this->formatResumeDetailed($resume),
            'message' => 'Резюме опубліковано',
        ]);
    }

    /**
     * GET /api/resumes/{id}/stepper-status
     * Отримати статус всіх кроків для Stepper-у
     */
    public function stepperStatus(Resume $resume): JsonResponse
    {
        $this->authorize('view', $resume);

        return response()->json([
            'data' => $resume->getStepperStatus(),
            'is_publishable' => $resume->isPublishable(),
        ]);
    }

    /**
     * ===== PRIVATE HELPERS =====
     */

    /**
     * Форматувати резюме для відповіді (коротка версія)
     */
    private function formatResume(Resume $resume): array
    {
        $personalInfo = $resume->personal_info;

        return [
            'id' => $resume->id,
            'title' => $resume->title,
            'status' => $resume->status,
            'full_name' => trim(($personalInfo['first_name'] ?? '') . ' ' . ($personalInfo['last_name'] ?? '')),
            'experiences_count' => $resume->experiences()->count(),
            'skills_count' => $resume->skills()->count(),
            'last_saved_at' => $resume->last_saved_at?->diffForHumans(),
            'updated_at' => $resume->updated_at,
        ];
    }

    /**
     * Форматувати резюме для відповіді (детальна версія)
     */
    private function formatResumeDetailed(Resume $resume): array
    {
        return [
            'id' => $resume->id,
            'title' => $resume->title,
            'status' => $resume->status,
            'personal_info' => $resume->personal_info,
            'location' => $resume->location,
            'notifications' => $resume->notifications,
            'additional_info' => $resume->additional_info,
            'experiences' => $resume->experiences()
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(fn($exp) => [
                    'id' => $exp->id,
                    'position' => $exp->position,
                    'company_name' => $exp->company_name,
                    'company_industry' => $exp->company_industry,
                    'start_date' => $exp->start_date?->format('Y-m-d'),
                    'end_date' => $exp->end_date?->format('Y-m-d'),
                    'is_current' => $exp->is_current,
                ]),
            'skills' => $resume->skills()
                ->pluck('skill_name')
                ->toArray(),
            'stepper_status' => $resume->getStepperStatus(),
            'is_publishable' => $resume->isPublishable(),
            'last_saved_at' => $resume->last_saved_at,
            'created_at' => $resume->created_at,
            'updated_at' => $resume->updated_at,
        ];
    }

    /**
     * Валідація особистої інформації
     */
    private function validatePersonalInfo(array $data): void
    {
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Невірний формат email');
            }
        }
    }

    /**
     * Валідація локації
     */
    private function validateLocation(array $data): void
    {
        // Опціональна валідація GPS координат
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $lat = $data['latitude'];
            $lng = $data['longitude'];

            if (!is_numeric($lat) || !is_numeric($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                throw new \Exception('Невірні GPS координати');
            }
        }
    }
}
```

---

## Mailable for Email Verification

```php
// app/Mail/VerificationCodeMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Код верифікації для My Job',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
            with: [
                'code' => $this->code,
                'expiresInMinutes' => 10,
            ],
        );
    }
}
```

---

## Email Template

```blade
// resources/views/emails/verification-code.blade.php

<h2>Код верифікації для My Job</h2>

<p>Ваш код верифікації:</p>

<div style="font-size: 32px; font-weight: bold; letter-spacing: 2px; margin: 20px 0;">
    {{ $code }}
</div>

<p>Код дійсний {{ $expiresInMinutes }} хвилин.</p>

<p>Якщо ви не запитували цей код, просто ігноруйте це повідомлення.</p>
```

---

## Policy (ResumePolicy)

```php
// app/Policies/ResumePolicy.php

namespace App\Policies;

use App\Models\Resume;
use App\Models\User;

class ResumePolicy
{
    public function view(User $user, Resume $resume): bool
    {
        return $user->id === $resume->user_id;
    }

    public function update(User $user, Resume $resume): bool
    {
        return $user->id === $resume->user_id;
    }

    public function delete(User $user, Resume $resume): bool
    {
        return $user->id === $resume->user_id;
    }
}
```

---

## Команди для інтеграції:

1. **Зареєструвати Policy** в `app/Providers/AuthServiceProvider.php`:
```php
protected $policies = [
    Resume::class => ResumePolicy::class,
];
```

2. **Створити Mailable**:
```bash
php artisan make:mail VerificationCodeMail
```

3. **Створити email view**:
```bash
php artisan make:mail VerificationCodeMail --markdown
```

4. **Тестування endpoints**:
```bash
php artisan serve
# Тест в Postman або curl
```

---

## Примеры HTTP запросів

### Создание резюме
```http
POST /api/resumes
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "Senior Laravel Developer"
}
```

### Обновление personal_info (debounce)
```http
PATCH /api/resumes/1
Content-Type: application/json
Authorization: Bearer {token}

{
  "personal_info": {
    "first_name": "Іван",
    "last_name": "Петренко"
  }
}
```

### Отправка кода верифікації
```http
POST /api/resumes/1/send-verification-code
Content-Type: application/json
Authorization: Bearer {token}

{
  "email": "ivan@example.com"
}
```

### Подтверждение email
```http
POST /api/resumes/1/verify-email
Content-Type: application/json
Authorization: Bearer {token}

{
  "email": "ivan@example.com",
  "code": "123456"
}
```

### Добавление опыта
```http
POST /api/resumes/1/experiences
Content-Type: application/json
Authorization: Bearer {token}

{
  "position": "Senior Developer",
  "company_name": "TechCorp",
  "company_industry": "IT",
  "start_date": "2020-01-15",
  "end_date": "2023-06-30",
  "is_current": false
}
```

### Получение статуса stepper
```http
GET /api/resumes/1/stepper-status
Authorization: Bearer {token}
```

Відповідь:
```json
{
  "data": {
    "personal_info": true,
    "email": false,
    "experience": true,
    "skills": false,
    "location": true,
    "notifications": true
  },
  "is_publishable": false
}
```

### Публікація резюме
```http
POST /api/resumes/1/publish
Authorization: Bearer {token}
```
