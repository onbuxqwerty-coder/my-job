<?php

declare(strict_types=1);

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AuthStep extends Component
{
    public Resume $resume;
    public array  $formData = [];

    public function mount(Resume $resume, array $formData = []): void
    {
        $this->resume   = $resume;
        $this->formData = $formData;

        if (Auth::check()) {
            $this->attachUserToResume();
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-steps.auth-step', [
            'isAuthenticated' => Auth::check(),
        ]);
    }

    public function checkAuth(): void
    {
        if (Auth::check()) {
            $this->attachUserToResume();
            $this->dispatch('auth-completed');
        }
    }

    private function attachUserToResume(): void
    {
        if ($this->resume->user_id === null) {
            $this->resume->update(['user_id' => Auth::id()]);
            session()->forget('pending_resume_id');
        }
    }
}
