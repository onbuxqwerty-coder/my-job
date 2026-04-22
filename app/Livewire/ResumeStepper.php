<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Resume;
use Livewire\Attributes\On;
use Livewire\Component;

class ResumeStepper extends Component
{
    public Resume $resume;
    public int    $currentStep   = 1;
    public array  $stepperStatus = [];
    public bool   $isPublishable = false;

    /** @var array<int, array{key: string, title: string, description: string}> */
    protected array $steps = [
        1 => ['key' => 'personal_info', 'title' => 'Картка-Візитка',  'description' => "Ім'я та прізвище"],
        2 => ['key' => 'auth',          'title' => 'Авторизація',      'description' => 'Вхід або реєстрація'],
        3 => ['key' => 'experience',    'title' => 'Досвід',           'description' => 'Посади та компанії'],
        4 => ['key' => 'skills',        'title' => 'Навички',          'description' => 'Технічні вміння'],
        5 => ['key' => 'location',      'title' => 'Локація',          'description' => 'Місто та адреса'],
        6 => ['key' => 'notifications', 'title' => 'Сповіщення',       'description' => "Канали зв'язку"],
    ];

    public function mount(
        Resume $resume,
        int    $currentStep   = 1,
        array  $stepperStatus = [],
        bool   $isPublishable = false,
    ): void {
        $this->resume        = $resume;
        $this->currentStep   = $currentStep;
        $this->stepperStatus = $stepperStatus;
        $this->isPublishable = $isPublishable;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-stepper', [
            'steps'        => $this->steps,
            'stepStatuses' => $this->getStepStatuses(),
        ]);
    }

    public function goToStep(int $stepNumber): void
    {
        $this->dispatch('go-to-step', step: $stepNumber);
    }

    #[On('update-stepper-status')]
    public function updateStepperStatus(array $status): void
    {
        $this->stepperStatus = $status;
    }

    // ===== Private helpers =====

    /** @return array<int, array{completed: bool, hasErrors: bool, status: string}> */
    private function getStepStatuses(): array
    {
        $statuses = [];

        foreach ($this->steps as $number => $step) {
            $isCompleted = $this->stepperStatus[$step['key']] ?? false;
            $hasErrors   = !$isCompleted && $this->hasStepErrors($step['key']);

            $statuses[$number] = [
                'completed' => $isCompleted,
                'hasErrors' => $hasErrors,
                'status'    => $this->resolveStatus($isCompleted, $hasErrors),
            ];
        }

        return $statuses;
    }

    private function hasStepErrors(string $stepKey): bool
    {
        return match ($stepKey) {
            'personal_info' => !$this->validatePersonalInfo(),
            'auth'          => !auth()->check(),
            default         => false,
        };
    }

    private function resolveStatus(bool $completed, bool $hasErrors): string
    {
        if ($completed) {
            return 'completed';
        }
        if ($hasErrors) {
            return 'error';
        }
        return 'empty';
    }

    private function validatePersonalInfo(): bool
    {
        $info = $this->resume->personal_info ?? [];
        return !empty($info['first_name']) && !empty($info['last_name']);
    }

}
