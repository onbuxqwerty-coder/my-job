<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Resume;
use Livewire\Attributes\On;
use Livewire\Component;

class ResumeWizard extends Component
{
    public Resume $resume;

    public int $currentStep  = 1;
    public int $totalSteps   = 6;

    public bool   $isSaving            = false;
    public string $saveMessage         = '';
    public bool   $saveMessageVisible  = false;

    public array $formData        = [];
    public array $validationErrors = [];
    public array $stepperStatus    = [];

    /** @var array<int,string> */
    protected array $steps = [
        1 => 'personal-info',
        2 => 'email',
        3 => 'experience',
        4 => 'skills',
        5 => 'location',
        6 => 'notifications',
    ];

    public function mount(Resume $resume): void
    {
        $this->resume   = $resume;
        $this->formData = [
            'personal_info'   => $resume->personal_info   ?? [],
            'location'        => $resume->location        ?? [],
            'notifications'   => $resume->notifications   ?? [],
            'additional_info' => $resume->additional_info ?? [],
        ];
        $this->updateStepperStatus();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-wizard', [
            'currentStepKey' => $this->steps[$this->currentStep] ?? 'personal-info',
            'steps'          => $this->steps,
        ]);
    }

    // ===== Navigation =====

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > $this->totalSteps) {
            return;
        }

        $this->saveChanges();
        $this->currentStep = $step;
    }

    #[On('go-to-step')]
    public function handleStepperClick(int $step): void
    {
        $this->goToStep($step);
    }

    public function nextStep(): void
    {
        if (!$this->isCurrentStepValid()) {
            return;
        }

        $this->goToStep($this->currentStep + 1);
    }

    public function previousStep(): void
    {
        $this->goToStep($this->currentStep - 1);
    }

    // ===== Form updates =====

    #[On('updateFormData')]
    public function updateFormData(string $section, mixed $key, mixed $value = null): void
    {
        if (!isset($this->formData[$section])) {
            $this->formData[$section] = [];
        }

        if (is_array($key)) {
            $this->formData[$section] = array_merge($this->formData[$section], $key);
        } else {
            $this->formData[$section][$key] = $value;
        }

        $this->dispatch('scheduleAutoSave');
    }

    public function onBlurField(string $section, string $key): void
    {
        $this->saveChanges();
    }

    // ===== Save =====

    public function saveChanges(): void
    {
        $this->isSaving = true;

        try {
            $info          = $this->formData['personal_info']   ?? [];
            $location      = $this->formData['location']        ?? [];
            $notifications = $this->formData['notifications']   ?? [];
            $additional    = $this->formData['additional_info'] ?? [];

            if (!empty($info)) {
                $this->resume->updatePersonalInfo($info);
            }
            if (!empty($location)) {
                $this->resume->updateLocation($location);
            }
            if (!empty($notifications)) {
                $this->resume->updateNotifications($notifications);
            }
            if (!empty($additional)) {
                $this->resume->updateAdditionalInfo($additional);
            }

            $this->resume->refresh();
            $this->updateStepperStatus();

            $this->saveMessage        = 'Всі зміни збережено';
            $this->saveMessageVisible = true;

            $this->dispatch('hideSaveMessage');
        } catch (\Exception $e) {
            $this->saveMessage        = 'Помилка при збереженні';
            $this->saveMessageVisible = true;
        } finally {
            $this->isSaving = false;
        }
    }

    #[On('hideSaveMessage')]
    public function hideSaveMessage(): void
    {
        $this->saveMessageVisible = false;
    }

    // ===== Publish =====

    public function publishResume(): void
    {
        if (!$this->resume->isPublishable()) {
            $this->validationErrors['publish'] = 'Будь ласка, заповніть всі критичні поля перед публікацією';
            return;
        }

        try {
            $this->resume->update(['status' => 'published']);
            $this->resume->refresh();
            $this->saveMessage        = 'Резюме опубліковано!';
            $this->saveMessageVisible = true;
            $this->dispatch('resume-published');
        } catch (\Exception $e) {
            $this->validationErrors['publish'] = 'Помилка при публікації';
        }
    }

    // ===== Delete =====

    public function deleteResume(): void
    {
        if ($this->resume->status === 'published') {
            $this->validationErrors['delete'] = 'Неможливо видалити опубліковане резюме';
            return;
        }

        try {
            $this->resume->delete();
            $this->dispatch('resume-deleted');
            $this->redirect(route('seeker.dashboard'));
        } catch (\Exception $e) {
            $this->validationErrors['delete'] = 'Помилка при видаленні';
        }
    }

    // ===== Step updated from child =====

    #[On('step-updated')]
    public function onStepUpdated(string $section, array $data): void
    {
        if (!isset($this->formData[$section])) {
            $this->formData[$section] = [];
        }
        $this->formData[$section] = array_merge($this->formData[$section], $data);
        $this->dispatch('scheduleAutoSave');
    }

    // ===== Private helpers =====

    private function isCurrentStepValid(): bool
    {
        return match ($this->currentStep) {
            1       => $this->validatePersonalInfo(),
            2       => $this->validateEmail(),
            default => true,
        };
    }

    private function validatePersonalInfo(): bool
    {
        $errors    = [];
        $firstName = $this->formData['personal_info']['first_name'] ?? '';
        $lastName  = $this->formData['personal_info']['last_name']  ?? '';

        if (empty($firstName)) {
            $errors['personal_info.first_name'] = "Ім'я обов'язкове";
        }
        if (empty($lastName)) {
            $errors['personal_info.last_name'] = "Прізвище обов'язкове";
        }

        $this->validationErrors = $errors;

        return empty($errors);
    }

    private function validateEmail(): bool
    {
        $errors          = [];
        $email           = $this->formData['personal_info']['email']             ?? '';
        $emailVerifiedAt = $this->formData['personal_info']['email_verified_at'] ?? null;

        if (empty($email)) {
            $errors['personal_info.email'] = "Email обов'язковий";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['personal_info.email'] = 'Невірний формат email';
        }

        if (empty($emailVerifiedAt)) {
            $errors['personal_info.email_verified_at'] = 'Email не верифіковано';
        }

        $this->validationErrors = $errors;

        return empty($errors);
    }

    private function updateStepperStatus(): void
    {
        $this->stepperStatus = $this->resume->getStepperStatus();
        $this->dispatch('update-stepper-status', status: $this->stepperStatus);
    }
}
