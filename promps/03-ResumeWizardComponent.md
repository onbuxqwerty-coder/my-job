# 03 — ResumeWizard Livewire Component (Main Wizard)

## Objetivo
Створити основний Livewire компонент для управління всім flow резюме:
- Управління стану резюме (personal_info, experiences, skills, location, notifications)
- Debounce PATCH запити (2-3 сек) + onBlur + step transition
- Вільна навігація між кроками
- Stepper з Status Indicators
- Збереження чорновика
- Индикатор "Збереження..." / "Всі зміни збережено"

---

## Livewire Component Class

```php
// app/Livewire/ResumeWizard.php

namespace App\Livewire;

use App\Models\Resume;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

class ResumeWizard extends Component
{
    public Resume $resume;
    public $currentStep = 1; // 1-6 кроків
    public $totalSteps = 6;
    
    // Флаги для UX
    public $isSaving = false;
    public $saveMessage = '';
    public $saveMessageVisible = false;
    
    // Дані резюме (локальна копія для debounce)
    public array $formData = [];
    
    // Validation errors
    public array $validationErrors = [];
    
    // Stepper status
    public array $stepperStatus = [];
    
    // Дебаунс таймер
    protected $debounceTimer = null;
    
    // Список кроків
    protected $steps = [
        1 => 'personal-info',   // Ім'я, прізвище, приватність
        2 => 'email',           // Email верифікація
        3 => 'experience',      // Досвід роботи
        4 => 'skills',          // Навички
        5 => 'location',        // Локація
        6 => 'notifications',   // Канали комунікації
    ];

    /**
     * Mount - інітіалізація компонента
     */
    public function mount(Resume $resume)
    {
        $this->resume = $resume;
        $this->formData = [
            'personal_info' => $resume->personal_info ?? [],
            'location' => $resume->location ?? [],
            'notifications' => $resume->notifications ?? [],
            'additional_info' => $resume->additional_info ?? [],
        ];
        $this->updateStepperStatus();
    }

    /**
     * Render - відображення компонента
     */
    public function render()
    {
        return view('livewire.resume-wizard', [
            'currentStepKey' => $this->steps[$this->currentStep] ?? 'personal-info',
            'steps' => $this->steps,
            'stepperStatus' => $this->stepperStatus,
            'formData' => $this->formData,
            'isPublishable' => $this->resume->isPublishable(),
            'isSaving' => $this->isSaving,
            'saveMessage' => $this->saveMessage,
            'saveMessageVisible' => $this->saveMessageVisible,
        ]);
    }

    /**
     * ===== НАВІГАЦІЯ =====
     */

    /**
     * Перейти на конкретний крок
     */
    public function goToStep($stepNumber)
    {
        if ($stepNumber < 1 || $stepNumber > $this->totalSteps) {
            return;
        }

        // Убедитесь что текущий шаг сохранен перед переходом
        $this->saveCurrentStepData();

        $this->currentStep = $stepNumber;
    }

    /**
     * Наступний крок
     */
    public function nextStep()
    {
        // Перевірити валідність поточного кроку
        if (!$this->isCurrentStepValid()) {
            return;
        }

        $this->goToStep($this->currentStep + 1);
    }

    /**
     * Попередній крок
     */
    public function previousStep()
    {
        $this->goToStep($this->currentStep - 1);
    }

    /**
     * ===== DEBOUNCE ЗБЕРЕЖЕННЯ =====
     */

    /**
     * Оновити дані в formData і запустити debounce
     */
    #[On('updateFormData')]
    public function updateFormData($section, $key, $value)
    {
        // Оновити локальну копію
        if (!isset($this->formData[$section])) {
            $this->formData[$section] = [];
        }

        if (is_array($key)) {
            // Якщо $key це масив (nested update)
            $this->formData[$section] = array_merge(
                $this->formData[$section],
                $key
            );
        } else {
            // Просто оновити одне поле
            $this->formData[$section][$key] = $value;
        }

        // Запустити debounce збереження
        $this->debounceSave();
    }

    /**
     * Debounce збереження (2-3 сек)
     * Реальна реалізація залежить від Livewire v3+ magic
     */
    public function debounceSave()
    {
        $this->dispatch('debounce-save', delay: 2500);
    }

    /**
     * Actual save (викликається після debounce)
     */
    #[On('debounce-save')]
    public function saveChanges()
    {
        $this->isSaving = true;

        try {
            $payloads = [
                'personal_info' => $this->formData['personal_info'] ?? [],
                'location' => $this->formData['location'] ?? [],
                'notifications' => $this->formData['notifications'] ?? [],
                'additional_info' => $this->formData['additional_info'] ?? [],
            ];

            // Відправити PATCH запит (можна через HTTP client)
            $response = $this->patchResume($payloads);

            if ($response['success']) {
                $this->saveMessage = 'Всі зміни збережено';
                $this->saveMessageVisible = true;
                $this->resume->refresh();
                $this->updateStepperStatus();

                // Приховати повідомлення через 2 сек
                $this->dispatch('hide-save-message', delay: 2000);
            }
        } catch (\Exception $e) {
            $this->saveMessage = 'Помилка при збереженні: ' . $e->getMessage();
            $this->saveMessageVisible = true;
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Збереження при onBlur
     */
    public function onBlurField($section, $key)
    {
        // Перевірити валідність поля
        $this->validateField($section, $key);

        // Відправити збереження одразу
        $this->saveChanges();
    }

    /**
     * ===== ЗБЕРЕЖЕННЯ ПОТОЧНОГО КРОКУ =====
     */

    public function saveCurrentStepData()
    {
        // Це буде викликано при переході на інший крок
        $this->saveChanges();
    }

    /**
     * ===== ВАЛІДАЦІЯ =====
     */

    /**
     * Перевірити, чи поточний крок валідний
     */
    public function isCurrentStepValid(): bool
    {
        return match ($this->currentStep) {
            1 => $this->validatePersonalInfo(),
            2 => $this->validateEmail(),
            3 => true, // Experience опціональна, але рекомендована
            4 => true, // Skills опціональна
            5 => true, // Location опціональна (можна пропустити)
            6 => true, // Notifications завжди валідна
            default => true,
        };
    }

    /**
     * Валідувати персональну інформацію
     */
    private function validatePersonalInfo(): bool
    {
        $errors = [];

        $firstName = $this->formData['personal_info']['first_name'] ?? '';
        $lastName = $this->formData['personal_info']['last_name'] ?? '';

        if (empty($firstName)) {
            $errors['personal_info.first_name'] = 'Ім\'я обов\'язкове';
        }

        if (empty($lastName)) {
            $errors['personal_info.last_name'] = 'Прізвище обов\'язкове';
        }

        $this->validationErrors = $errors;

        return empty($errors);
    }

    /**
     * Валідувати email
     */
    private function validateEmail(): bool
    {
        $email = $this->formData['personal_info']['email'] ?? '';
        $emailVerifiedAt = $this->formData['personal_info']['email_verified_at'] ?? null;

        $errors = [];

        if (empty($email)) {
            $errors['personal_info.email'] = 'Email обов\'язковий';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['personal_info.email'] = 'Невірний формат email';
        }

        if (empty($emailVerifiedAt)) {
            $errors['personal_info.email_verified_at'] = 'Email не верифіковано';
        }

        $this->validationErrors = $errors;

        return empty($errors);
    }

    /**
     * Валідувати конкретне поле
     */
    private function validateField($section, $key)
    {
        // Опціонально: додати валідацію для окремих полів
        // Це буде викликано при onBlur
    }

    /**
     * ===== STEPPER STATUS =====
     */

    /**
     * Оновити статус кроків для Stepper-у
     */
    private function updateStepperStatus()
    {
        $this->stepperStatus = $this->resume->getStepperStatus();
    }

    /**
     * ===== ПУБЛІКАЦІЯ =====
     */

    /**
     * Спробувати опублікувати резюме
     */
    public function publishResume()
    {
        // Перевірити, чи резюме готове до публікації
        if (!$this->resume->isPublishable()) {
            $this->validationErrors = [
                'publish' => 'Будь ласка, заповніть всі критичні поля перед публікацією',
            ];
            return;
        }

        try {
            // Відправити запит на публікацію
            $response = $this->publishResumeViaAPI();

            if ($response['success']) {
                $this->resume->refresh();
                $this->dispatch('resume-published');
                $this->saveMessage = 'Резюме опубліковано!';
                $this->saveMessageVisible = true;
            }
        } catch (\Exception $e) {
            $this->validationErrors['publish'] = 'Помилка при публікації: ' . $e->getMessage();
        }
    }

    /**
     * ===== УДАЛЕННЯ =====
     */

    /**
     * Видалити draft резюме
     */
    public function deleteResume()
    {
        if ($this->resume->status === 'published') {
            $this->validationErrors['delete'] = 'Неможливо видалити опубліковане резюме';
            return;
        }

        try {
            $this->resume->delete();
            $this->dispatch('resume-deleted');
        } catch (\Exception $e) {
            $this->validationErrors['delete'] = 'Помилка при видаленні: ' . $e->getMessage();
        }
    }

    /**
     * ===== API CALLS =====
     */

    /**
     * PATCH резюме через API
     */
    private function patchResume(array $payloads): array
    {
        try {
            $response = \Http::withToken(auth()->user()->createToken('api')->plainTextToken)
                ->patch(
                    route('api.resumes.update', $this->resume->id),
                    array_filter($payloads, fn($v) => !empty($v))
                )
                ->throw()
                ->json();

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Публікація через API
     */
    private function publishResumeViaAPI(): array
    {
        try {
            $response = \Http::withToken(auth()->user()->createToken('api')->plainTextToken)
                ->post(route('api.resumes.publish', $this->resume->id))
                ->throw()
                ->json();

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ===== EVENTS =====
     */

    /**
     * Приховати повідомлення про збереження
     */
    #[On('hide-save-message')]
    public function hideSaveMessage()
    {
        $this->saveMessageVisible = false;
    }

    /**
     * Слухач на событи дочерних компонентів
     */
    #[On('step-updated')]
    public function onStepUpdated($section, $data)
    {
        $this->updateFormData($section, $data, null);
    }
}
```

---

## Livewire View (Blade)

```blade
// resources/views/livewire/resume-wizard.blade.php

<div class="resume-wizard min-h-screen bg-gray-50">
    <!-- HEADER с индикатором сохранения -->
    <div class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Конструктор резюме</h1>
                
                <!-- Save indicator -->
                <div class="text-sm">
                    @if ($isSaving)
                        <span class="inline-flex items-center gap-2 text-amber-600">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Збереження...
                        </span>
                    @elseif ($saveMessageVisible)
                        <span class="inline-flex items-center gap-2 text-green-600">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ $saveMessage }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- SIDEBAR - STEPPER -->
            <aside class="lg:col-span-1">
                @livewire('resume-stepper', ['resume' => $resume, 'currentStep' => $currentStep, 'stepperStatus' => $stepperStatus], key('stepper-' . $resume->id))
            </aside>

            <!-- MAIN CONTENT -->
            <main class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow">
                    
                    <!-- STEP 1: Personal Info -->
                    @if ($currentStep === 1)
                        @livewire('resume-steps.card-step', ['resume' => $resume, 'formData' => $formData], key('step-1-' . $resume->id))
                    @endif

                    <!-- STEP 2: Email Verification -->
                    @if ($currentStep === 2)
                        @livewire('resume-steps.email-step', ['resume' => $resume, 'formData' => $formData], key('step-2-' . $resume->id))
                    @endif

                    <!-- STEP 3: Experience -->
                    @if ($currentStep === 3)
                        @livewire('resume-steps.experience-step', ['resume' => $resume, 'formData' => $formData], key('step-3-' . $resume->id))
                    @endif

                    <!-- STEP 4: Skills -->
                    @if ($currentStep === 4)
                        @livewire('resume-steps.skills-step', ['resume' => $resume, 'formData' => $formData], key('step-4-' . $resume->id))
                    @endif

                    <!-- STEP 5: Location -->
                    @if ($currentStep === 5)
                        @livewire('resume-steps.location-step', ['resume' => $resume, 'formData' => $formData], key('step-5-' . $resume->id))
                    @endif

                    <!-- STEP 6: Notifications -->
                    @if ($currentStep === 6)
                        @livewire('resume-steps.notifications-step', ['resume' => $resume, 'formData' => $formData], key('step-6-' . $resume->id))
                    @endif

                    <!-- FOOTER - BUTTONS -->
                    <div class="px-6 py-6 border-t border-gray-200 flex items-center justify-between gap-4">
                        <button 
                            wire:click="previousStep"
                            @if ($currentStep === 1) disabled @endif
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            ← Назад
                        </button>

                        <div class="flex-1 text-center">
                            <span class="text-sm text-gray-500">
                                Крок {{ $currentStep }} з {{ $totalSteps }}
                            </span>
                        </div>

                        @if ($currentStep < $totalSteps)
                            <button 
                                wire:click="nextStep"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                            >
                                Далі →
                            </button>
                        @else
                            @if ($isPublishable)
                                <button 
                                    wire:click="publishResume"
                                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                                >
                                    Опублікувати
                                </button>
                            @else
                                <button 
                                    disabled
                                    class="px-6 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed"
                                    title="Заповніть критичні поля для публікації"
                                >
                                    Опублікувати
                                </button>
                            @endif
                        @endif
                    </div>

                    <!-- Validation errors -->
                    @if (!empty($validationErrors))
                        <div class="px-6 py-4 bg-red-50 border border-red-200 rounded-lg">
                            @foreach ($validationErrors as $field => $message)
                                <p class="text-sm text-red-700">{{ $message }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>

@script
<script>
    // Debounce сохранения (2-3 сек)
    Livewire.on('debounce-save', () => {
        setTimeout(() => {
            Livewire.dispatch('debounce-save');
        }, 2500);
    });

    // Приховати повідомлення про сохранення через 2 сек
    Livewire.on('hide-save-message', () => {
        setTimeout(() => {
            Livewire.dispatch('hide-save-message');
        }, 2000);
    });
</script>
@endscript
```

---

## Команди для інтеграції:

1. **Створити Livewire компонент**:
```bash
php artisan livewire:make ResumeWizard
```

2. **Переважна Route** (`routes/web.php`):
```php
Route::middleware('auth')->group(function () {
    Route::get('/resumes/create', function () {
        $resume = auth()->user()->resumes()->create([
            'title' => 'Нове резюме',
            'status' => 'draft',
        ]);
        return view('resumes.create', ['resume' => $resume]);
    })->name('resumes.create');

    Route::get('/resumes/{resume}/edit', function (Resume $resume) {
        $this->authorize('update', $resume);
        return view('resumes.edit', ['resume' => $resume]);
    })->name('resumes.edit');
});
```

3. **View** (`resources/views/resumes/create.blade.php`):
```blade
@extends('layouts.app')

@section('content')
    @livewire('resume-wizard', ['resume' => $resume])
@endsection
```

---

## Наступні кроки:

Наступний промпт створить **ResumeStepper** (sidebar з статус-індикаторами) та дочірні компоненти для кожного кроку.
