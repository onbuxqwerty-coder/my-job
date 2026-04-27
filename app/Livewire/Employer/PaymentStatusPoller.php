<?php

declare(strict_types=1);

namespace App\Livewire\Employer;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PaymentStatusPoller extends Component
{
    public Vacancy $vacancy;
    public string $startedAt;
    public int $maxWaitSeconds = 300;

    public function mount(Vacancy $vacancy): void
    {
        $this->vacancy   = $vacancy;
        $this->startedAt = now()->toIso8601String();
    }

    public function refresh(): void
    {
        $this->vacancy->refresh();
    }

    #[Computed]
    public function isConfirmed(): bool
    {
        return $this->vacancy->status === VacancyStatus::Active
            && $this->vacancy->expires_at !== null
            && $this->vacancy->expires_at->isAfter(Carbon::parse($this->startedAt));
    }

    #[Computed]
    public function isTimeout(): bool
    {
        return Carbon::parse($this->startedAt)->diffInSeconds(now()) > $this->maxWaitSeconds;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.employer.payment-status-poller');
    }
}
