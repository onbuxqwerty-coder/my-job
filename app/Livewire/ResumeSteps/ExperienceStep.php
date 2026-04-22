<?php

declare(strict_types=1);

namespace App\Livewire\ResumeSteps;

use App\Models\Experience;
use App\Models\Resume;
use Livewire\Component;

class ExperienceStep extends Component
{
    public Resume $resume;
    public array  $formData    = [];
    public array  $experiences = [];
    public array  $errors      = [];

    public array $newExperience = [
        'position'         => '',
        'company_name'     => '',
        'company_industry' => '',
        'start_date'       => '',
        'end_date'         => '',
        'is_current'       => false,
    ];

    public bool $isAddingNew = false;

    public function mount(Resume $resume, array $formData = []): void
    {
        $this->resume   = $resume;
        $this->formData = $formData;
        $this->loadExperiences();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-steps.experience-step', [
            'canAddMore' => count($this->experiences) < 5,
        ]);
    }

    public function addExperience(): void
    {
        $this->errors = [];

        if (count($this->experiences) >= 5) {
            $this->errors['general'] = 'Максимум 5 записів про досвід';
            return;
        }

        if (empty($this->newExperience['position'])) {
            $this->errors['position'] = "Посада обов'язкова";
            return;
        }

        if (empty($this->newExperience['company_name'])) {
            $this->errors['company_name'] = "Назва компанії обов'язкова";
            return;
        }

        if (empty($this->newExperience['start_date'])) {
            $this->errors['start_date'] = "Дата початку обов'язкова";
            return;
        }

        if (!$this->newExperience['is_current']) {
            if (empty($this->newExperience['end_date'])) {
                $this->errors['end_date'] = "Дата закінчення обов'язкова";
                return;
            }
            if ($this->newExperience['end_date'] <= $this->newExperience['start_date']) {
                $this->errors['end_date'] = 'Дата закінчення повинна бути після дати початку';
                return;
            }
        }

        try {
            $data = $this->newExperience;
            if ($data['is_current']) {
                $data['end_date'] = null;
            }

            $this->resume->experiences()->create($data);
            $this->loadExperiences();
            $this->resetNewExperience();
            $this->isAddingNew = false;

            $this->dispatch('step-updated', section: 'experience', data: []);
        } catch (\Exception) {
            $this->errors['general'] = 'Помилка при додаванні досвіду';
        }
    }

    public function deleteExperience(int $experienceId): void
    {
        try {
            Experience::whereKey($experienceId)
                ->where('resume_id', $this->resume->id)
                ->delete();

            $this->loadExperiences();
            $this->dispatch('step-updated', section: 'experience', data: []);
        } catch (\Exception) {
            $this->errors['general'] = 'Помилка при видаленні';
        }
    }

    public function toggleCurrentJob(): void
    {
        $this->newExperience['is_current'] = !$this->newExperience['is_current'];

        if ($this->newExperience['is_current']) {
            $this->newExperience['end_date'] = '';
        }
    }

    // ===== Private =====

    private function loadExperiences(): void
    {
        $this->experiences = $this->resume->experiences()
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Experience $exp) => [
                'id'               => $exp->id,
                'position'         => $exp->position,
                'company_name'     => $exp->company_name,
                'company_industry' => $exp->company_industry,
                'start_date'       => $exp->start_date?->format('Y-m-d'),
                'end_date'         => $exp->end_date?->format('Y-m-d'),
                'is_current'       => $exp->is_current,
            ])
            ->toArray();
    }

    private function resetNewExperience(): void
    {
        $this->newExperience = [
            'position'         => '',
            'company_name'     => '',
            'company_industry' => '',
            'start_date'       => '',
            'end_date'         => '',
            'is_current'       => false,
        ];
        $this->errors = [];
    }
}
