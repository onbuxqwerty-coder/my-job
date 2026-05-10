<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Events\ApplicationStatusChanged;
use App\Models\Application;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public int    $applicationId;
    public string $newStatus    = '';
    public string $comment      = '';
    public string $saved        = '';

    public function mount(int $applicationId): void
    {
        $this->applicationId = $applicationId;

        abort_unless(
            Application::whereHas('vacancy', fn ($q) => $q->where('company_id', auth()->user()->company->id))
                ->where('id', $applicationId)
                ->exists(),
            403
        );

        // Авто-перехід Pending → Viewed (системна дія, changedBy = null)
        $application = Application::find($applicationId);
        if ($application && $application->status === ApplicationStatus::Pending) {
            $application->logStatus(ApplicationStatus::Viewed);
        }
    }

    #[Computed]
    public function application(): Application
    {
        return Application::with(['vacancy.company', 'statusLogs'])
            ->findOrFail($this->applicationId);
    }

    /** @return array<ApplicationStatus> */
    public function availableStatuses(): array
    {
        return [
            ApplicationStatus::Reviewing,
            ApplicationStatus::Interview,
            ApplicationStatus::Offered,
            ApplicationStatus::Rejected,
        ];
    }

    public function save(): void
    {
        $this->validate([
            'newStatus' => 'required|string',
        ]);

        $status = ApplicationStatus::tryFrom($this->newStatus);

        if ($status === null || ! in_array($status, $this->availableStatuses(), true)) {
            $this->addError('newStatus', 'Недійсний статус');
            return;
        }

        $oldStatus   = $this->application->status;
        $application = $this->application;

        $application->logStatus($status, auth()->user(), $this->comment ?: null);

        event(new ApplicationStatusChanged($application, $oldStatus, $status, auth()->user()));

        $this->newStatus = '';
        $this->comment   = '';
        $this->saved     = 'ok';
        unset($this->application);
    }
}; ?>

<div class="space-y-4">
    {{-- Поточний статус --}}
    <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500 dark:text-gray-400">Поточний статус:</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
            bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
            {{ $this->application->status->label() }}
        </span>
    </div>

    <form wire:submit="save" class="space-y-3">

        {{-- Select статусу --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Новий статус
            </label>
            <select wire:model="newStatus"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                           bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm
                           focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                <option value="">— Виберіть статус</option>
                @foreach($this->availableStatuses() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('newStatus')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Коментар --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Коментар <span class="text-gray-400">(необов'язково)</span>
            </label>
            <textarea wire:model="comment" rows="3"
                      placeholder="Залиште пояснення для кандидата..."
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                             bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm
                             focus:ring-2 focus:ring-orange-400 focus:border-transparent transition resize-none">
            </textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="px-5 py-2 bg-orange-500 hover:bg-orange-600 disabled:opacity-60
                           text-white text-sm font-bold rounded-lg transition">
                <span wire:loading.remove wire:target="save">Зберегти</span>
                <span wire:loading wire:target="save">Збереження...</span>
            </button>

            @if($saved === 'ok')
                <span class="text-sm text-green-600 dark:text-green-400" wire:key="saved-msg">
                    Статус оновлено
                </span>
            @endif
        </div>

    </form>
</div>
