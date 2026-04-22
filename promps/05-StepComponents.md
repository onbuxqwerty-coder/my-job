# 05 — Step Components (CardStep, EmailStep, ExperienceStep, SkillsStep, LocationStep, NotificationsStep)

## Objetivo
Створити 6 дочірніх Livewire компонентів для кожного кроку Resume Builder з debounce збереженням та валідацією.

---

## 1. CardStep Component (Ім'я, прізвище, приватність)

### Class

```php
// app/Livewire/ResumeSteps/CardStep.php

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use Livewire\Component;
use Livewire\Attributes\On;

class CardStep extends Component
{
    public Resume $resume;
    public array $formData = [];
    public array $errors = [];

    public function mount(Resume $resume, array $formData = [])
    {
        $this->resume = $resume;
        $this->formData = $formData;
    }

    public function render()
    {
        return view('livewire.resume-steps.card-step', [
            'formData' => $this->formData,
            'errors' => $this->errors,
        ]);
    }

    /**
     * Оновити ім'я
     */
    #[On('debounce-save')]
    public function updateFirstName($value)
    {
        $this->formData['personal_info']['first_name'] = $value;
        $this->validateFirstName();
        $this->debounceSave();
    }

    /**
     * Оновити прізвище
     */
    #[On('debounce-save')]
    public function updateLastName($value)
    {
        $this->formData['personal_info']['last_name'] = $value;
        $this->validateLastName();
        $this->debounceSave();
    }

    /**
     * Оновити приватність
     */
    public function updatePrivacy($value)
    {
        $this->formData['personal_info']['privacy'] = (bool) $value;
        $this->dispatch('update-form-data', section: 'personal_info', key: 'privacy', value: $value);
    }

    /**
     * Оновити прозорість
     */
    public function updateTransparency($value)
    {
        $this->formData['personal_info']['transparency'] = (bool) $value;
        $this->dispatch('update-form-data', section: 'personal_info', key: 'transparency', value: $value);
    }

    /**
     * Валідація
     */
    private function validateFirstName()
    {
        $value = $this->formData['personal_info']['first_name'] ?? '';
        
        if (empty($value)) {
            $this->errors['first_name'] = 'Ім\'я обов\'язкове';
        } else {
            unset($this->errors['first_name']);
        }
    }

    private function validateLastName()
    {
        $value = $this->formData['personal_info']['last_name'] ?? '';
        
        if (empty($value)) {
            $this->errors['last_name'] = 'Прізвище обов\'язкове';
        } else {
            unset($this->errors['last_name']);
        }
    }

    private function debounceSave()
    {
        $this->dispatch('debounce-save');
    }
}
```

### View

```blade
// resources/views/livewire/resume-steps/card-step.blade.php

<div class="px-6 py-6 space-y-6">
    <div class="space-y-2">
        <h2 class="text-xl font-bold text-gray-900">Ваша картка-візитка</h2>
        <p class="text-sm text-gray-600">Основна інформація про вас для роботодавців</p>
    </div>

    <!-- Ім'я -->
    <div>
        <label class="block text-sm font-semibold text-gray-900 mb-2">Ім'я</label>
        <input
            type="text"
            wire:model.debounce-2500ms="formData.personal_info.first_name"
            wire:blur="updateFirstName($event.target.value)"
            placeholder="Наприклад: Іван"
            class="
                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                {{ isset($errors['first_name']) ? 'border-red-500' : 'border-gray-300' }}
            "
        />
        @if (isset($errors['first_name']))
            <p class="mt-2 text-sm text-red-600">{{ $errors['first_name'] }}</p>
        @endif
    </div>

    <!-- Прізвище -->
    <div>
        <label class="block text-sm font-semibold text-gray-900 mb-2">Прізвище</label>
        <input
            type="text"
            wire:model.debounce-2500ms="formData.personal_info.last_name"
            wire:blur="updateLastName($event.target.value)"
            placeholder="Наприклад: Петренко"
            class="
                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                {{ isset($errors['last_name']) ? 'border-red-500' : 'border-gray-300' }}
            "
        />
        @if (isset($errors['last_name']))
            <p class="mt-2 text-sm text-red-600">{{ $errors['last_name'] }}</p>
        @endif
    </div>

    <!-- Приватність -->
    <div class="space-y-3 pt-4 border-t border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900">Видимість резюме</h3>
        
        <label class="flex items-center gap-3 cursor-pointer">
            <input
                type="checkbox"
                wire:model="formData.personal_info.privacy"
                wire:change="updatePrivacy($event.target.checked)"
                class="w-4 h-4 rounded border-gray-300"
            />
            <span class="text-sm text-gray-700">
                <span class="font-semibold">Приватність</span>
                <span class="text-gray-600">— розміщуйте резюме анонімно</span>
            </span>
        </label>

        <label class="flex items-center gap-3 cursor-pointer">
            <input
                type="checkbox"
                wire:model="formData.personal_info.transparency"
                wire:change="updateTransparency($event.target.checked)"
                class="w-4 h-4 rounded border-gray-300"
            />
            <span class="text-sm text-gray-700">
                <span class="font-semibold">Прозорість</span>
                <span class="text-gray-600">— компанії бачать ваші переглади вакансій</span>
            </span>
        </label>
    </div>

    <!-- Info Box -->
    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <span class="font-semibold">💡 Порада:</span>
            Точні дані допомагають роботодавцям краще зрозуміти вас та запропонувати релевантні позиції.
        </p>
    </div>
</div>
```

---

## 2. EmailStep Component (Email верифікація)

### Class

```php
// app/Livewire/ResumeSteps/EmailStep.php

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use Livewire\Component;
use Livewire\Attributes\On;

class EmailStep extends Component
{
    public Resume $resume;
    public array $formData = [];
    public array $errors = [];
    
    public $email = '';
    public $verificationCode = '';
    public $codeSent = false;
    public $isVerified = false;
    public $isVerifying = false;
    public $countdown = 0;

    public function mount(Resume $resume, array $formData = [])
    {
        $this->resume = $resume;
        $this->formData = $formData;
        $this->email = $formData['personal_info']['email'] ?? '';
        $this->isVerified = !empty($formData['personal_info']['email_verified_at'] ?? '');
    }

    public function render()
    {
        return view('livewire.resume-steps.email-step', [
            'email' => $this->email,
            'verificationCode' => $this->verificationCode,
            'codeSent' => $this->codeSent,
            'isVerified' => $this->isVerified,
            'isVerifying' => $this->isVerifying,
            'countdown' => $this->countdown,
            'errors' => $this->errors,
        ]);
    }

    /**
     * Надіслати код верифікації
     */
    public function sendVerificationCode()
    {
        // Валідувати email
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Невірний формат email';
            return;
        }

        try {
            $response = \Http::withToken(auth()->user()->createToken('api')->plainTextToken)
                ->post(route('api.resumes.send-verification-code', $this->resume->id), [
                    'email' => $this->email,
                ])
                ->throw()
                ->json();

            $this->codeSent = true;
            $this->countdown = 32; // 32 секунди
            $this->startCountdown();
            unset($this->errors['email']);
        } catch (\Exception $e) {
            $this->errors['email'] = 'Помилка при надіслані коду. Спробуйте ще раз.';
        }
    }

    /**
     * Запустити countdown для повторного надсилання
     */
    private function startCountdown()
    {
        $this->dispatch('start-countdown', duration: 32);
    }

    /**
     * Оновити countdown
     */
    #[On('tick-countdown')]
    public function tickCountdown($remaining)
    {
        $this->countdown = $remaining;
    }

    /**
     * Верифікувати код
     */
    public function verifyEmail()
    {
        if (empty($this->verificationCode)) {
            $this->errors['code'] = 'Введіть код';
            return;
        }

        $this->isVerifying = true;

        try {
            $response = \Http::withToken(auth()->user()->createToken('api')->plainTextToken)
                ->post(route('api.resumes.verify-email', $this->resume->id), [
                    'email' => $this->email,
                    'code' => $this->verificationCode,
                ])
                ->throw()
                ->json();

            $this->isVerified = true;
            $this->codeSent = false;
            $this->verificationCode = '';
            $this->formData['personal_info']['email'] = $this->email;
            $this->formData['personal_info']['email_verified_at'] = now()->toIso8601String();
            
            $this->dispatch('step-updated', 'personal_info', [
                'email' => $this->email,
                'email_verified_at' => now()->toIso8601String(),
            ]);

            unset($this->errors['code']);
        } catch (\Exception $e) {
            $this->errors['code'] = 'Невірний код. Спробуйте ще раз.';
        } finally {
            $this->isVerifying = false;
        }
    }

    /**
     * Змінити email
     */
    public function changeEmail()
    {
        $this->isVerified = false;
        $this->codeSent = false;
        $this->verificationCode = '';
        unset($this->errors['code']);
    }
}
```

### View

```blade
// resources/views/livewire/resume-steps/email-step.blade.php

<div class="px-6 py-6 space-y-6">
    <div class="space-y-2">
        <h2 class="text-xl font-bold text-gray-900">Верифікація email</h2>
        <p class="text-sm text-gray-600">Ми надішлемо код для перевірки вашої адреси</p>
    </div>

    @if ($isVerified)
        <!-- ✓ Успішна верифікація -->
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div>
                    <h3 class="font-semibold text-green-900">Email верифіковано</h3>
                    <p class="text-sm text-green-800">{{ $email }}</p>
                </div>
            </div>

            <button
                wire:click="changeEmail"
                class="mt-4 text-sm text-green-700 hover:text-green-900 font-semibold underline"
            >
                Змінити email
            </button>
        </div>
    @else
        <!-- 1. Ввід email -->
        @if (!$codeSent)
            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">Ваш email</label>
                <input
                    type="email"
                    wire:model="email"
                    placeholder="your@email.com"
                    class="
                        w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                        {{ isset($errors['email']) ? 'border-red-500' : 'border-gray-300' }}
                    "
                />
                @if (isset($errors['email']))
                    <p class="mt-2 text-sm text-red-600">{{ $errors['email'] }}</p>
                @endif
            </div>

            <button
                wire:click="sendVerificationCode"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition"
            >
                Надіслати код
            </button>
        @else
            <!-- 2. Ввід коду -->
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-900">
                    Код відправлено на <span class="font-semibold">{{ $email }}</span>
                </p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">Код верифікації (6 цифр)</label>
                <input
                    type="text"
                    wire:model="verificationCode"
                    maxlength="6"
                    inputmode="numeric"
                    placeholder="000000"
                    class="
                        w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                        tracking-widest text-center text-2xl font-bold
                        {{ isset($errors['code']) ? 'border-red-500' : 'border-gray-300' }}
                    "
                />
                @if (isset($errors['code']))
                    <p class="mt-2 text-sm text-red-600">{{ $errors['code'] }}</p>
                @endif
            </div>

            <button
                wire:click="verifyEmail"
                wire:loading.attr="disabled"
                class="
                    w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition
                    disabled:opacity-50 disabled:cursor-not-allowed
                "
            >
                @if ($isVerifying)
                    <span wire:loading>Верифікація...</span>
                @else
                    Підтвердити код
                @endif
            </button>

            <!-- Resend -->
            <div class="text-center">
                @if ($countdown > 0)
                    <p class="text-sm text-gray-600">
                        Повторити через {{ $countdown }} сек.
                    </p>
                @else
                    <button
                        wire:click="sendVerificationCode"
                        class="text-sm text-blue-600 hover:text-blue-700 font-semibold"
                    >
                        Надіслати код ще раз
                    </button>
                @endif
            </div>

            <!-- Change email -->
            <button
                wire:click="changeEmail"
                class="w-full text-sm text-gray-600 hover:text-gray-900 underline"
            >
                Змінити email
            </button>
        @endif
    @endif

    <!-- Info Box -->
    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <span class="font-semibold">💡 Важливо:</span>
            Email дійсно впливає на можливість отримання пропозицій від роботодавців.
        </p>
    </div>
</div>

@script
<script>
    Livewire.on('start-countdown', (data) => {
        let remaining = data.duration;
        
        const timer = setInterval(() => {
            remaining--;
            Livewire.dispatch('tick-countdown', { remaining });
            
            if (remaining <= 0) {
                clearInterval(timer);
            }
        }, 1000);
    });
</script>
@endscript
```

---

## 3. ExperienceStep Component

### Class

```php
// app/Livewire/ResumeSteps/ExperienceStep.php

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use App\Models\Experience;
use Livewire\Component;

class ExperienceStep extends Component
{
    public Resume $resume;
    public array $formData = [];
    public array $experiences = [];
    public array $errors = [];
    
    public $newExperience = [
        'position' => '',
        'company_name' => '',
        'company_industry' => '',
        'start_date' => '',
        'end_date' => '',
        'is_current' => false,
    ];

    public $isAddingNew = false;

    public function mount(Resume $resume, array $formData = [])
    {
        $this->resume = $resume;
        $this->formData = $formData;
        $this->loadExperiences();
    }

    public function render()
    {
        return view('livewire.resume-steps.experience-step', [
            'experiences' => $this->experiences,
            'newExperience' => $this->newExperience,
            'isAddingNew' => $this->isAddingNew,
            'errors' => $this->errors,
            'canAddMore' => count($this->experiences) < 5,
        ]);
    }

    /**
     * Завантажити досвід з БД
     */
    private function loadExperiences()
    {
        $this->experiences = $this->resume->experiences()
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
            ])
            ->toArray();
    }

    /**
     * Додати новий досвід
     */
    public function addExperience()
    {
        if (count($this->experiences) >= 5) {
            $this->errors['general'] = 'Максимум 5 записів про досвід';
            return;
        }

        // Валідація
        if (empty($this->newExperience['position'])) {
            $this->errors['position'] = 'Посада обов\'язкова';
            return;
        }

        if (empty($this->newExperience['company_name'])) {
            $this->errors['company_name'] = 'Назва компанії обов\'язкова';
            return;
        }

        if (empty($this->newExperience['start_date'])) {
            $this->errors['start_date'] = 'Дата початку обов\'язкова';
            return;
        }

        // Якщо не поточна робота, перевірити дату закінчення
        if (!$this->newExperience['is_current'] && empty($this->newExperience['end_date'])) {
            $this->errors['end_date'] = 'Дата закінчення обов\'язкова';
            return;
        }

        if (!$this->newExperience['is_current']) {
            if ($this->newExperience['end_date'] <= $this->newExperience['start_date']) {
                $this->errors['end_date'] = 'Дата закінчення повинна бути після дати початку';
                return;
            }
        }

        try {
            $experience = $this->resume->experiences()->create($this->newExperience);

            // Перезагрузити список
            $this->loadExperiences();
            
            // Очистити форму
            $this->newExperience = [
                'position' => '',
                'company_name' => '',
                'company_industry' => '',
                'start_date' => '',
                'end_date' => '',
                'is_current' => false,
            ];
            $this->isAddingNew = false;
            $this->errors = [];

            $this->dispatch('step-updated', 'experience', []);
        } catch (\Exception $e) {
            $this->errors['general'] = 'Помилка при додаванні досвіду';
        }
    }

    /**
     * Видалити досвід
     */
    public function deleteExperience($experienceId)
    {
        try {
            Experience::findOrFail($experienceId)->delete();
            $this->loadExperiences();
            $this->dispatch('step-updated', 'experience', []);
        } catch (\Exception $e) {
            $this->errors['general'] = 'Помилка при видаленні';
        }
    }

    /**
     * Переключити на "поточна робота"
     */
    public function toggleCurrentJob()
    {
        $this->newExperience['is_current'] = !$this->newExperience['is_current'];
        
        if ($this->newExperience['is_current']) {
            $this->newExperience['end_date'] = '';
        }
    }
}
```

### View

```blade
// resources/views/livewire/resume-steps/experience-step.blade.php

<div class="px-6 py-6 space-y-6">
    <div class="space-y-2">
        <h2 class="text-xl font-bold text-gray-900">Досвід роботи</h2>
        <p class="text-sm text-gray-600">Додайте ваші минулі та поточні посади (макс. 5)</p>
    </div>

    <!-- Список досвідів -->
    @if (!empty($experiences))
        <div class="space-y-4">
            @foreach ($experiences as $exp)
                <div class="p-4 border border-gray-200 rounded-lg hover:shadow-sm transition">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $exp['position'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $exp['company_name'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ \Carbon\Carbon::parse($exp['start_date'])->format('M Y') }}
                                —
                                @if ($exp['is_current'])
                                    <span class="font-semibold text-green-600">Поточна посада</span>
                                @else
                                    {{ \Carbon\Carbon::parse($exp['end_date'])->format('M Y') }}
                                @endif
                            </p>
                        </div>
                        <button
                            wire:click="deleteExperience({{ $exp['id'] }})"
                            class="text-red-600 hover:text-red-700"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
            <p class="text-sm text-gray-600">Досвід роботи не додано</p>
        </div>
    @endif

    <!-- Форма для додавання нового досвіду -->
    @if ($isAddingNew || empty($experiences))
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">
                {{ empty($experiences) ? 'Додайте перший досвід' : 'Додати ще' }}
            </h3>

            <div class="space-y-4">
                <!-- Посада -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Посада</label>
                    <input
                        type="text"
                        wire:model="newExperience.position"
                        placeholder="Наприклад: Senior Laravel Developer"
                        class="
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                            {{ isset($errors['position']) ? 'border-red-500' : 'border-gray-300' }}
                        "
                    />
                    @if (isset($errors['position']))
                        <p class="mt-1 text-sm text-red-600">{{ $errors['position'] }}</p>
                    @endif
                </div>

                <!-- Компанія -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Назва компанії</label>
                    <input
                        type="text"
                        wire:model="newExperience.company_name"
                        placeholder="Наприклад: TechCorp"
                        class="
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                            {{ isset($errors['company_name']) ? 'border-red-500' : 'border-gray-300' }}
                        "
                    />
                    @if (isset($errors['company_name']))
                        <p class="mt-1 text-sm text-red-600">{{ $errors['company_name'] }}</p>
                    @endif
                </div>

                <!-- Галузь -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Галузь</label>
                    <input
                        type="text"
                        wire:model="newExperience.company_industry"
                        placeholder="Наприклад: IT / Software Development"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <!-- Дата початку -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Дата початку</label>
                    <input
                        type="date"
                        wire:model="newExperience.start_date"
                        class="
                            w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                            {{ isset($errors['start_date']) ? 'border-red-500' : 'border-gray-300' }}
                        "
                    />
                    @if (isset($errors['start_date']))
                        <p class="mt-1 text-sm text-red-600">{{ $errors['start_date'] }}</p>
                    @endif
                </div>

                <!-- Поточна робота? -->
                <label class="flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:change="toggleCurrentJob"
                        wire:model="newExperience.is_current"
                        class="w-4 h-4 rounded border-gray-300"
                    />
                    <span class="text-sm text-gray-700">Я роблю тут зараз</span>
                </label>

                <!-- Дата закінчення (якщо не поточна) -->
                @if (!$newExperience['is_current'])
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Дата закінчення</label>
                        <input
                            type="date"
                            wire:model="newExperience.end_date"
                            class="
                                w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                                {{ isset($errors['end_date']) ? 'border-red-500' : 'border-gray-300' }}
                            "
                        />
                        @if (isset($errors['end_date']))
                            <p class="mt-1 text-sm text-red-600">{{ $errors['end_date'] }}</p>
                        @endif
                    </div>
                @endif

                <!-- Кнопки -->
                <div class="flex gap-3 pt-4">
                    <button
                        wire:click="addExperience"
                        class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition"
                    >
                        Додати
                    </button>
                    @if (!empty($experiences))
                        <button
                            wire:click="$set('isAddingNew', false)"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-semibold transition"
                        >
                            Скасувати
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @elseif ($canAddMore)
        <button
            wire:click="$set('isAddingNew', true)"
            class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 font-semibold transition"
        >
            + Додати ще один досвід
        </button>
    @endif

    <!-- Info Box -->
    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <span class="font-semibold">💡 Порада:</span>
            Більше досвіду = більше шансів знайти роботу. Мінімум одна посада допоможе роботодавцям зрозуміти ваш рівень.
        </p>
    </div>
</div>
```

---

## 4. SkillsStep Component

### Class

```php
// app/Livewire/ResumeSteps/SkillsStep.php

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use App\Models\Skill;
use Livewire\Component;

class SkillsStep extends Component
{
    public Resume $resume;
    public array $formData = [];
    public array $skills = [];
    public array $suggestions = [];
    
    public $newSkill = '';
    public $searchQuery = '';

    protected $predefinedSkills = [
        'Laravel', 'PHP', 'JavaScript', 'React', 'Vue.js',
        'HTML', 'CSS', 'Tailwind CSS', 'Node.js', 'MySQL',
        'PostgreSQL', 'MongoDB', 'Git', 'Docker', 'AWS',
        'REST API', 'GraphQL', 'TypeScript', 'Python', 'SQL',
    ];

    public function mount(Resume $resume, array $formData = [])
    {
        $this->resume = $resume;
        $this->formData = $formData;
        $this->loadSkills();
        $this->generateSuggestions();
    }

    public function render()
    {
        return view('livewire.resume-steps.skills-step', [
            'skills' => $this->skills,
            'suggestions' => $this->suggestions,
            'newSkill' => $this->newSkill,
            'searchResults' => $this->getSearchResults(),
        ]);
    }

    /**
     * Завантажити навички з БД
     */
    private function loadSkills()
    {
        $this->skills = $this->resume->skills()
            ->pluck('skill_name')
            ->toArray();
    }

    /**
     * Отримати результати пошуку
     */
    public function getSearchResults()
    {
        if (empty($this->searchQuery)) {
            return [];
        }

        return array_filter(
            $this->predefinedSkills,
            fn($skill) => stripos($skill, $this->searchQuery) !== false && !in_array($skill, $this->skills)
        );
    }

    /**
     * Генерувати рекомендації на основі досвіду
     */
    private function generateSuggestions()
    {
        $suggestions = [];

        // Якщо є Laravel або PHP досвід
        if ($this->hasExperience('laravel') || $this->hasExperience('php')) {
            $suggestions = array_merge($suggestions, ['Livewire', 'Blade', 'Eloquent']);
        }

        // Якщо є JavaScript досвід
        if ($this->hasExperience('javascript')) {
            $suggestions = array_merge($suggestions, ['React', 'Vue.js', 'Node.js']);
        }

        $this->suggestions = array_diff($suggestions, $this->skills);
    }

    /**
     * Перевірити, чи користувач має досвід з технологією
     */
    private function hasExperience($technology): bool
    {
        return $this->resume->experiences()
            ->where('company_industry', 'ILIKE', '%' . $technology . '%')
            ->orWhere('position', 'ILIKE', '%' . $technology . '%')
            ->exists();
    }

    /**
     * Додати навичку
     */
    public function addSkill($skill = null)
    {
        $skillName = $skill ?? trim($this->newSkill);

        if (empty($skillName)) {
            return;
        }

        if (in_array($skillName, $this->skills)) {
            return; // Навичка вже додана
        }

        try {
            $this->resume->skills()->create(['skill_name' => $skillName]);
            $this->skills[] = $skillName;
            $this->newSkill = '';
            $this->searchQuery = '';
            $this->generateSuggestions();

            $this->dispatch('step-updated', 'skills', []);
        } catch (\Exception $e) {
            // Error
        }
    }

    /**
     * Видалити навичку
     */
    public function removeSkill($skill)
    {
        try {
            $this->resume->skills()
                ->where('skill_name', $skill)
                ->delete();

            $this->skills = array_diff($this->skills, [$skill]);
            $this->generateSuggestions();

            $this->dispatch('step-updated', 'skills', []);
        } catch (\Exception $e) {
            // Error
        }
    }
}
```

### View

```blade
// resources/views/livewire/resume-steps/skills-step.blade.php

<div class="px-6 py-6 space-y-6">
    <div class="space-y-2">
        <h2 class="text-xl font-bold text-gray-900">Навички</h2>
        <p class="text-sm text-gray-600">Додайте технічні вміння та інструменти, якими ви володієте</p>
    </div>

    <!-- Додані навички (теги) -->
    @if (!empty($skills))
        <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">Ваші навички:</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($skills as $skill)
                    <div class="inline-flex items-center gap-2 bg-blue-100 text-blue-900 px-3 py-1 rounded-full text-sm">
                        <span>{{ $skill }}</span>
                        <button
                            wire:click="removeSkill('{{ $skill }}')"
                            class="hover:text-blue-600"
                        >
                            ✕
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Форма для додавання -->
    <div class="relative">
        <label class="block text-sm font-semibold text-gray-900 mb-2">Додати навичку</label>
        <input
            type="text"
            wire:model.debounce-300ms="searchQuery"
            placeholder="Напр. Laravel, React, Python..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />

        <!-- Search results dropdown -->
        @if (!empty($searchResults))
            <div class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                @foreach ($searchResults as $result)
                    <button
                        wire:click="addSkill('{{ $result }}')"
                        class="w-full text-left px-4 py-3 hover:bg-blue-50 text-sm border-b border-gray-100 last:border-b-0"
                    >
                        {{ $result }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Рекомендації -->
    @if (!empty($suggestions))
        <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">Рекомендовані навички:</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($suggestions as $suggestion)
                    <button
                        wire:click="addSkill('{{ $suggestion }}')"
                        class="px-3 py-1 border border-gray-300 rounded-full text-sm text-gray-700 hover:bg-gray-50 hover:border-blue-400 transition"
                    >
                        + {{ $suggestion }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Info Box -->
    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <span class="font-semibold">💡 Порада:</span>
            Навички дійсно впливають на матчинг з роботодавцями. Додайте всі релевантні технології!
        </p>
    </div>
</div>
```

---

## 5. LocationStep Component (Скорочено)

```php
// app/Livewire/ResumeSteps/LocationStep.php

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use Livewire\Component;

class LocationStep extends Component
{
    public Resume $resume;
    public array $formData = [];
    
    public $city = '';
    public $cityId = null;
    public $street = '';
    public $building = '';
    public $latitude = null;
    public $longitude = null;
    public $noLocationBinding = false;
    public $citySuggestions = [];

    public function mount(Resume $resume, array $formData = [])
    {
        $this->resume = $resume;
        $this->formData = $formData;
        
        $location = $formData['location'] ?? [];
        $this->city = $location['city'] ?? '';
        $this->cityId = $location['city_id'] ?? null;
        $this->street = $location['street'] ?? '';
        $this->building = $location['building'] ?? '';
        $this->latitude = $location['latitude'] ?? null;
        $this->longitude = $location['longitude'] ?? null;
        $this->noLocationBinding = $location['no_location_binding'] ?? false;
    }

    public function render()
    {
        return view('livewire.resume-steps.location-step', [
            'city' => $this->city,
            'street' => $this->street,
            'building' => $this->building,
            'noLocationBinding' => $this->noLocationBinding,
            'citySuggestions' => $this->citySuggestions,
        ]);
    }

    /**
     * Пошук міст (Smart City Search)
     */
    public function searchCities($query)
    {
        if (strlen($query) < 2) {
            $this->citySuggestions = [];
            return;
        }

        // Запит до Smart City Search компонента або API
        try {
            $response = \Http::get('https://nominatim.openstreetmap.org/search', [
                'q' => $query . ', Ukraine',
                'format' => 'json',
                'limit' => 5,
            ])->json();

            $this->citySuggestions = collect($response)
                ->map(fn($item) => [
                    'name' => $item['display_name'] ?? $item['name'],
                    'lat' => $item['lat'],
                    'lon' => $item['lon'],
                ])
                ->toArray();
        } catch (\Exception $e) {
            $this->citySuggestions = [];
        }
    }

    /**
     * Вибрати місто
     */
    public function selectCity($cityName, $lat, $lon)
    {
        $this->city = $cityName;
        $this->latitude = $lat;
        $this->longitude = $lon;
        $this->citySuggestions = [];
        $this->saveLocation();
    }

    /**
     * Переключити "Без привязки до міста"
     */
    public function toggleNoLocationBinding()
    {
        $this->noLocationBinding = !$this->noLocationBinding;
        
        if ($this->noLocationBinding) {
            $this->city = '';
            $this->street = '';
            $this->building = '';
        }

        $this->saveLocation();
    }

    /**
     * Зберегти локацію
     */
    public function saveLocation()
    {
        $this->dispatch('update-form-data', section: 'location', key: [
            'city' => $this->city,
            'street' => $this->street,
            'building' => $this->building,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'no_location_binding' => $this->noLocationBinding,
        ]);
    }
}
```

---

## 6. NotificationsStep Component (Скорочено)

```php
// app/Livewire/ResumeSteps/NotificationsStep.php

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use Livewire\Component;

class NotificationsStep extends Component
{
    public Resume $resume;
    public array $formData = [];
    public array $notifications = [];

    public function mount(Resume $resume, array $formData = [])
    {
        $this->resume = $resume;
        $this->formData = $formData;
        $this->notifications = $formData['notifications'] ?? [];
    }

    public function render()
    {
        return view('livewire.resume-steps.notifications-step', [
            'notifications' => $this->notifications,
        ]);
    }

    /**
     * Переключити канал сповіщення
     */
    public function toggleChannel($channel)
    {
        $this->notifications[$channel] = !($this->notifications[$channel] ?? false);
        
        $this->dispatch('update-form-data', section: 'notifications', key: $channel, value: $this->notifications[$channel]);
    }
}
```

Blade view для NotificationsStep:

```blade
// resources/views/livewire/resume-steps/notifications-step.blade.php

<div class="px-6 py-6 space-y-6">
    <div class="space-y-2">
        <h2 class="text-xl font-bold text-gray-900">Сповіщення</h2>
        <p class="text-sm text-gray-600">Виберіть, як ви хочете отримувати пропозиції від роботодавців</p>
    </div>

    <!-- Канали -->
    <div class="space-y-3">
        @foreach ([
            'site' => 'На сайті My Job',
            'email' => 'На email',
            'sms' => 'SMS',
            'telegram' => 'Telegram',
            'viber' => 'Viber',
            'whatsapp' => 'WhatsApp',
        ] as $channel => $label)
            <label class="flex items-center gap-3 cursor-pointer p-4 border border-gray-200 rounded-lg hover:bg-blue-50 transition">
                <input
                    type="checkbox"
                    wire:change="toggleChannel('{{ $channel }}')"
                    wire:model="notifications.{{ $channel }}"
                    class="w-4 h-4 rounded border-gray-300"
                />
                <span class="text-sm font-semibold text-gray-900">{{ $label }}</span>
            </label>
        @endforeach
    </div>

    <!-- Info -->
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-900">
            <span class="font-semibold">ℹ️ Інформація:</span>
            Вибрані канали будуть використовуватись для надсилання пропозицій від роботодавців.
        </p>
    </div>
</div>
```

---

## Команди для запуску:

```bash
php artisan livewire:make ResumeSteps/CardStep
php artisan livewire:make ResumeSteps/EmailStep
php artisan livewire:make ResumeSteps/ExperienceStep
php artisan livewire:make ResumeSteps/SkillsStep
php artisan livewire:make ResumeSteps/LocationStep
php artisan livewire:make ResumeSteps/NotificationsStep
```

---

## Наступні кроки:

Розпочинаю розробку **шостого промпту** з тестами (PHPUnit + Livewire + Dusk).
