# 04 — ResumeStepper Component (Sidebar with Status Indicators)

## Objetivo
Створити Livewire компонент для Stepper-у в сайдбарі:
- Відображення всіх 6 кроків
- Status Indicators: ● (not filled), ⚠️ (errors), ✓ (completed)
- Вільна навігація (click на будь-який крок)
- Highlight поточного кроку
- Приємна анімація та переходи

---

## Livewire Component Class

```php
// app/Livewire/ResumeStepper.php

namespace App\Livewire;

use App\Models\Resume;
use Livewire\Component;

class ResumeStepper extends Component
{
    public Resume $resume;
    public $currentStep = 1;
    public $stepperStatus = [];

    protected $steps = [
        1 => [
            'key' => 'personal-info',
            'title' => 'Картка-Візитка',
            'description' => 'Ім\'я та прізвище',
            'icon' => 'user',
        ],
        2 => [
            'key' => 'email',
            'title' => 'Email',
            'description' => 'Верифікація',
            'icon' => 'mail',
        ],
        3 => [
            'key' => 'experience',
            'title' => 'Досвід',
            'description' => 'Посади та компанії',
            'icon' => 'briefcase',
        ],
        4 => [
            'key' => 'skills',
            'title' => 'Навички',
            'description' => 'Технічні вміння',
            'icon' => 'code',
        ],
        5 => [
            'key' => 'location',
            'title' => 'Локація',
            'description' => 'Місто та адреса',
            'icon' => 'map-pin',
        ],
        6 => [
            'key' => 'notifications',
            'title' => 'Сповіщення',
            'description' => 'Канали зв\'язку',
            'icon' => 'bell',
        ],
    ];

    public function mount(Resume $resume, $currentStep = 1, $stepperStatus = [])
    {
        $this->resume = $resume;
        $this->currentStep = $currentStep;
        $this->stepperStatus = $stepperStatus;
    }

    public function render()
    {
        return view('livewire.resume-stepper', [
            'steps' => $this->steps,
            'currentStep' => $this->currentStep,
            'stepperStatus' => $this->stepperStatus,
            'stepStatuses' => $this->getStepStatuses(),
        ]);
    }

    /**
     * Отримати статус для кожного кроку
     * Returns: ['completed' => bool, 'hasErrors' => bool]
     */
    private function getStepStatuses()
    {
        $statuses = [];
        
        foreach ($this->steps as $stepNumber => $step) {
            $stepKey = $step['key'];
            $isCompleted = $this->stepperStatus[$stepKey] ?? false;
            $hasErrors = !$isCompleted && $this->hasStepErrors($stepKey);

            $statuses[$stepNumber] = [
                'completed' => $isCompleted,
                'hasErrors' => $hasErrors,
                'status' => $this->getStatusLabel($isCompleted, $hasErrors),
            ];
        }

        return $statuses;
    }

    /**
     * Визначити, чи є помилки на кроці
     */
    private function hasStepErrors($stepKey): bool
    {
        return match ($stepKey) {
            'personal-info' => !$this->validatePersonalInfo(),
            'email' => !$this->validateEmail(),
            'experience' => false, // Опціональна
            'skills' => false, // Опціональна
            'location' => false, // Опціональна
            'notifications' => false, // Завжди валідна
            default => false,
        };
    }

    /**
     * Отримати лейбл статусу
     */
    private function getStatusLabel($isCompleted, $hasErrors)
    {
        if ($isCompleted) {
            return 'completed'; // ✓
        }
        if ($hasErrors) {
            return 'error'; // ⚠️
        }
        return 'empty'; // ●
    }

    /**
     * Валідувати персональну інформацію
     */
    private function validatePersonalInfo(): bool
    {
        $info = $this->resume->personal_info;
        return !empty($info['first_name']) && !empty($info['last_name']);
    }

    /**
     * Валідувати email
     */
    private function validateEmail(): bool
    {
        $info = $this->resume->personal_info;
        return !empty($info['email']) && !empty($info['email_verified_at']);
    }

    /**
     * Отримати CSS класс для статусу
     */
    public function getStatusClass($status): string
    {
        return match ($status) {
            'completed' => 'text-green-600 bg-green-50',
            'error' => 'text-red-600 bg-red-50',
            'empty' => 'text-gray-400 bg-gray-50',
            default => 'text-gray-400 bg-gray-50',
        };
    }

    /**
     * Отримати іконку статусу (SVG inline)
     */
    public function getStatusIcon($status): string
    {
        return match ($status) {
            'completed' => '✓',
            'error' => '!',
            'empty' => '●',
            default => '●',
        };
    }

    /**
     * Перейти на крок (click на stepper)
     */
    public function goToStep($stepNumber)
    {
        $this->dispatch('go-to-step', step: $stepNumber);
    }

    /**
     * Оновити поточний крок (слухач від parent ResumeWizard)
     */
    public function updateCurrentStep($step)
    {
        $this->currentStep = $step;
    }

    /**
     * Оновити статус кроків (слухач від parent ResumeWizard)
     */
    public function updateStepperStatus($status)
    {
        $this->stepperStatus = $status;
    }
}
```

---

## Blade View

```blade
// resources/views/livewire/resume-stepper.blade.php

<nav class="space-y-2">
    <!-- Title -->
    <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900">Прогрес</h3>
    </div>

    <!-- Steps -->
    <div class="px-2 py-4 space-y-1">
        @foreach ($steps as $stepNumber => $step)
            @php
                $isCurrentStep = $currentStep === $stepNumber;
                $stepStatus = $stepStatuses[$stepNumber] ?? [];
                $statusLabel = $stepStatus['status'] ?? 'empty';
                $isCompleted = $stepStatus['completed'] ?? false;
                $hasErrors = $stepStatus['hasErrors'] ?? false;
            @endphp

            <button
                wire:click="goToStep({{ $stepNumber }})"
                class="
                    w-full text-left px-4 py-3 rounded-lg transition-all duration-200
                    {{ $isCurrentStep 
                        ? 'bg-blue-50 border-2 border-blue-500 shadow-sm' 
                        : 'border-2 border-transparent hover:bg-gray-50' 
                    }}
                    focus:outline-none focus:ring-2 focus:ring-blue-400
                "
            >
                <div class="flex items-start gap-3">
                    <!-- Status Indicator -->
                    <div class="flex-shrink-0 mt-1">
                        <div class="relative w-6 h-6 flex items-center justify-center rounded-full">
                            <!-- Completed (✓) -->
                            @if ($isCompleted)
                                <div class="w-6 h-6 bg-green-600 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            <!-- Errors (⚠️) -->
                            @elseif ($hasErrors)
                                <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            <!-- Empty (●) -->
                            @else
                                <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                            @endif

                            <!-- Current Step Indicator (pulse) -->
                            @if ($isCurrentStep)
                                <div class="absolute inset-0 rounded-full border-2 border-blue-400 animate-pulse"></div>
                            @endif
                        </div>
                    </div>

                    <!-- Step Info -->
                    <div class="flex-1 min-w-0">
                        <h4 class="
                            text-sm font-semibold
                            {{ $isCurrentStep ? 'text-blue-900' : 'text-gray-900' }}
                        ">
                            {{ $step['title'] }}
                        </h4>
                        <p class="
                            text-xs
                            {{ $isCurrentStep ? 'text-blue-700' : 'text-gray-500' }}
                        ">
                            {{ $step['description'] }}
                        </p>

                        <!-- Error Message -->
                        @if ($hasErrors)
                            <div class="mt-1 text-xs text-red-600 font-medium">
                                Потребує уваги
                            </div>
                        @endif
                    </div>

                    <!-- Arrow (current step) -->
                    @if ($isCurrentStep)
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endif
                </div>
            </button>
        @endforeach
    </div>

    <!-- Summary -->
    <div class="px-4 py-4 border-t border-gray-200 bg-gray-50 rounded-lg">
        <div class="text-xs text-gray-600">
            <p class="font-semibold text-gray-900 mb-2">Статус резюме:</p>
            <ul class="space-y-1">
                <li class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 bg-green-600 rounded-full"></span>
                    <span>Заповнено</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 bg-red-600 rounded-full"></span>
                    <span>Помилка</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 bg-gray-400 rounded-full"></span>
                    <span>Не заповнено</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="px-4 py-4 border-t border-gray-200 space-y-2">
        <button
            wire:click="$parent.publishResume"
            @if (!($isPublishable ?? false)) disabled @endif
            class="
                w-full px-4 py-2 rounded-lg font-medium transition-colors
                {{ ($isPublishable ?? false)
                    ? 'bg-green-600 text-white hover:bg-green-700'
                    : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                }}
            "
        >
            Опублікувати
        </button>

        <button
            wire:click="$parent.deleteResume"
            @if ($resume->status === 'published') disabled @endif
            class="
                w-full px-4 py-2 rounded-lg font-medium transition-colors
                border border-red-300 text-red-600 hover:bg-red-50
                {{ $resume->status === 'published' ? 'opacity-50 cursor-not-allowed' : '' }}
            "
        >
            Видалити чорновик
        </button>
    </div>
</nav>

<!-- Tooltips for status indicators -->
@script
<script>
    // Опціонально: додати tooltips при hover на статус індикатори
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', (e) => {
            const tooltip = document.createElement('div');
            tooltip.className = 'absolute bg-gray-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap';
            tooltip.textContent = el.dataset.tooltip;
            document.body.appendChild(tooltip);

            const rect = el.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';

            el.addEventListener('mouseleave', () => tooltip.remove(), { once: true });
        });
    });
</script>
@endscript
```

---

## Parent Component Integration (ResumeWizard)

Додай до `ResumeWizard.php`:

```php
/**
 * Слухач на перехід на крок від Stepper-у
 */
#[On('go-to-step')]
public function handleStepperClick($step)
{
    $this->goToStep($step);
}

/**
 * Оновити Stepper статус при зміні формданих
 */
public function updateStepperStatus()
{
    $this->stepperStatus = $this->resume->getStepperStatus();
    
    // Відправити update до дочірнього компонента
    $this->dispatch('update-stepper-status', status: $this->stepperStatus);
}
```

Додай до `resume-wizard.blade.php`:

```blade
<!-- У sidebar -->
@livewire('resume-stepper', [
    'resume' => $resume, 
    'currentStep' => $currentStep, 
    'stepperStatus' => $stepperStatus,
    'isPublishable' => $isPublishable,
], key('stepper-' . $resume->id . '-' . $currentStep))

<!-- Слухаємо на update від parent -->
@script
<script>
    Livewire.on('update-stepper-status', (data) => {
        Livewire.dispatch('update-stepper-status', { status: data.status });
    });
</script>
@endscript
```

---

## CSS Classes (Tailwind)

У файлі конфігу `tailwind.config.js` переконайтесь, що включені анімації:

```js
module.exports = {
    theme: {
        extend: {
            animation: {
                'pulse': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
        },
    },
}
```

---

## Accessibility

Додати ARIA атрибути для доступності:

```blade
<button
    wire:click="goToStep({{ $stepNumber }})"
    aria-label="Перейти на крок {{ $stepNumber }}: {{ $step['title'] }}"
    aria-current="{{ $isCurrentStep ? 'step' : 'false' }}"
    aria-disabled="{{ $stepNumber > $currentStep ? 'true' : 'false' }}"
    {{ ... }}
>
```

---

## Команди для запуску:

```bash
php artisan livewire:make ResumeStepper
# Скопіюєте код до компонента та view
```

---

## Наступні Кроки:

Розпочинаю розробку **п'ятого промпту** з дочірніми компонентами для кожного кроку (CardStep, EmailStep, ExperienceStep, SkillsStep, LocationStep, NotificationsStep).
