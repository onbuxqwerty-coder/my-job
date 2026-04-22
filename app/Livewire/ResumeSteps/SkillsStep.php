<?php

declare(strict_types=1);

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SkillsStep extends Component
{
    public Resume $resume;
    public array  $formData    = [];
    public array  $skills      = [];
    public array  $suggestions = [];

    public string $newSkill          = '';
    public string $searchQuery       = '';
    public bool   $showSearchResults = false;

    protected array $predefinedSkills = [
        'Laravel', 'PHP', 'JavaScript', 'React', 'Vue.js',
        'HTML', 'CSS', 'Tailwind CSS', 'Node.js', 'MySQL',
        'PostgreSQL', 'MongoDB', 'Git', 'Docker', 'AWS',
        'REST API', 'GraphQL', 'TypeScript', 'Python', 'SQL',
        'Redis', 'Linux', 'Nginx', 'Livewire', 'Blade',
    ];

    public function mount(Resume $resume, array $formData = []): void
    {
        $this->resume   = $resume;
        $this->formData = $formData;
        $this->loadSkills();
        $this->generateSuggestions();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-steps.skills-step', [
            'searchResults' => $this->getSearchResults(),
        ]);
    }

    public function updatedSearchQuery(): void
    {
        $this->showSearchResults = strlen($this->searchQuery) >= 1;
    }

    public function closeSearchResults(): void
    {
        $this->showSearchResults = false;
    }

    public function addSkill(?string $skill = null): void
    {
        $skillName = trim($skill ?? $this->searchQuery ?? $this->newSkill);

        if (empty($skillName) || in_array($skillName, $this->skills, true)) {
            return;
        }

        try {
            $this->resume->skills()->create(['skill_name' => $skillName]);
            $this->skills[]          = $skillName;
            $this->newSkill          = '';
            $this->searchQuery       = '';
            $this->showSearchResults = false;
            $this->generateSuggestions();
            $this->dispatch('step-updated', section: 'skills', data: []);
        } catch (\Exception) {
        }
    }

    public function removeSkill(string $skill): void
    {
        try {
            $this->resume->skills()->where('skill_name', $skill)->delete();
            $this->skills = array_values(array_diff($this->skills, [$skill]));
            $this->generateSuggestions();
            $this->dispatch('step-updated', section: 'skills', data: []);
        } catch (\Exception) {
        }
    }

    public function getSearchResults(): array
    {
        if (strlen($this->searchQuery) < 1) {
            return [];
        }

        return array_values(array_filter(
            $this->predefinedSkills,
            fn (string $s) => stripos($s, $this->searchQuery) !== false
                && !in_array($s, $this->skills, true)
        ));
    }

    // ===== Private =====

    private function loadSkills(): void
    {
        $this->skills = $this->resume->skills()->pluck('skill_name')->toArray();
    }

    private function generateSuggestions(): void
    {
        $suggestions = [];

        if ($this->hasExperienceWith('laravel') || $this->hasExperienceWith('php')) {
            $suggestions = array_merge($suggestions, ['Livewire', 'Blade', 'Eloquent', 'PHP']);
        }

        if ($this->hasExperienceWith('javascript') || $this->hasExperienceWith('js')) {
            $suggestions = array_merge($suggestions, ['React', 'Vue.js', 'Node.js', 'TypeScript']);
        }

        $this->suggestions = array_values(array_diff($suggestions, $this->skills));
    }

    private function hasExperienceWith(string $keyword): bool
    {
        return $this->resume->experiences()
            ->where(function ($q) use ($keyword): void {
                $q->where('company_industry', 'like', "%{$keyword}%")
                  ->orWhere('position', 'like', "%{$keyword}%");
            })
            ->exists();
    }
}
