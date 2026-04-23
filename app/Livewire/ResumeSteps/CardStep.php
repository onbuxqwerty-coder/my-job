<?php

declare(strict_types=1);

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use Livewire\Attributes\On;
use Livewire\Component;

class CardStep extends Component
{
    public Resume $resume;
    public array  $formData = [];
    public array  $errors   = [];

    public function mount(Resume $resume, array $formData = []): void
    {
        $this->resume   = $resume;
        $this->formData = $formData;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-steps.card-step');
    }

    public function updatePrivacy(): void
    {
        $value = (bool) ($this->formData['personal_info']['privacy'] ?? false);
        $this->syncToParent('privacy', $value);
    }

    public function updateTransparency(): void
    {
        $value = (bool) ($this->formData['personal_info']['transparency'] ?? false);
        $this->syncToParent('transparency', $value);
    }

    #[On('validate-personal-info')]
    public function onValidate(): void
    {
        $this->validateFields();
    }

    public function updatedFormData(): void
    {
        $this->dispatch('updateFormData',
            section: 'personal_info',
            key: $this->formData['personal_info'] ?? [],
            value: null,
        );
    }

    private function validateFields(): void
    {
        $this->errors = [];

        if (empty($this->formData['personal_info']['first_name'] ?? '')) {
            $this->errors['first_name'] = "Ім'я обов'язкове";
        }
        if (empty($this->formData['personal_info']['last_name'] ?? '')) {
            $this->errors['last_name'] = "Прізвище обов'язкове";
        }

        $phone = $this->formData['personal_info']['phone'] ?? '';
        if (!empty($phone) && $phone !== '+38 (0') {
            if (!preg_match('/^\+38 \(0\d{2}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
                $this->errors['phone'] = 'Введіть номер у форматі +38 (0XX) XXX-XX-XX';
            }
        }
    }

    private function syncToParent(string $key, mixed $value): void
    {
        $this->dispatch('updateFormData',
            section: 'personal_info',
            key: $key,
            value: $value,
        );
    }
}
