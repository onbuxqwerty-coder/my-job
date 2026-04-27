<?php

declare(strict_types=1);

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Livewire\Volt\Component;

new class extends Component {
    public int $vacancyId;

    public Vacancy $vacancy;

    public int $pollInterval = 60;

    public function mount(int $vacancyId): void
    {
        $this->vacancyId = $vacancyId;
        $this->vacancy   = Vacancy::findOrFail($vacancyId);
    }

    public function refresh(): void
    {
        $this->vacancy = Vacancy::findOrFail($this->vacancyId);
    }

    public function isCritical(): bool
    {
        return $this->vacancy->is_active
            && $this->vacancy->hours_left !== null
            && $this->vacancy->hours_left < 24;
    }

    public function isWarning(): bool
    {
        return $this->vacancy->is_active
            && $this->vacancy->hours_left !== null
            && $this->vacancy->hours_left < 72
            && ! $this->isCritical();
    }

    public function progressPercent(): int
    {
        if (! $this->vacancy->is_active || ! $this->vacancy->expires_at || ! $this->vacancy->published_at) {
            return 0;
        }

        $total   = $this->vacancy->published_at->diffInSeconds($this->vacancy->expires_at, absolute: true);
        $elapsed = $this->vacancy->published_at->diffInSeconds(now(), absolute: true);

        if ($total === 0) {
            return 100;
        }

        return min(100, max(0, (int) round($elapsed / $total * 100)));
    }

    public function expiredAgoLabel(): ?string
    {
        if ($this->vacancy->status !== VacancyStatus::Expired || ! $this->vacancy->expires_at) {
            return null;
        }

        return 'Завершено ' . $this->vacancy->expires_at->locale('uk')->diffForHumans();
    }
}; ?>

<div
    wire:poll.{{ $pollInterval }}s="refresh"
    class="rounded-lg border bg-white p-6 shadow-sm
        {{ $this->isCritical() ? 'border-red-300' : ($this->isWarning() ? 'border-yellow-300' : 'border-gray-200') }}"
    aria-live="polite"
>
    {{-- Статус бейдж --}}
    <div class="flex items-center gap-2 mb-4">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $vacancy->status->badgeClass() }}">
            <span class="w-1.5 h-1.5 rounded-full
                {{ $vacancy->status === VacancyStatus::Active
                    ? ($this->isCritical() ? 'bg-green-500 animate-pulse' : 'bg-green-500')
                    : ($vacancy->status === VacancyStatus::Expired ? 'bg-yellow-500'
                    : ($vacancy->status === VacancyStatus::Archived ? 'bg-red-500' : 'bg-gray-500')) }}
            "></span>
            {{ $vacancy->status->label() }}
        </span>

        @if($this->isCritical())
            <span class="text-xs text-red-600 font-medium">⚠ Терміново</span>
        @endif
    </div>

    {{-- Основний лічильник --}}
    <div class="space-y-1 mb-4">
        <p class="text-xl font-semibold
            {{ $this->isCritical() ? 'text-red-700' : ($this->isWarning() ? 'text-yellow-700' : 'text-gray-900') }}">
            {{ $vacancy->countdown_label }}
        </p>

        @if($vacancy->is_active && $vacancy->expires_at)
            <p class="text-sm text-gray-500">
                до {{ $vacancy->expires_at->locale('uk')->isoFormat('D MMMM YYYY, HH:mm') }}
            </p>
        @elseif($expiredLabel = $this->expiredAgoLabel())
            <p class="text-sm text-gray-500">{{ $expiredLabel }}</p>
        @endif
    </div>

    {{-- Прогрес-бар --}}
    @if($vacancy->is_active)
        <div class="mb-5">
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div
                    class="h-full transition-all duration-1000 ease-out
                        {{ $this->isCritical() ? 'bg-red-500' : ($this->isWarning() ? 'bg-yellow-500' : 'bg-green-500') }}"
                    style="width: {{ $this->progressPercent() }}%"
                    role="progressbar"
                    aria-valuenow="{{ $this->progressPercent() }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>
            <p class="mt-1 text-xs text-gray-400 text-right">{{ $this->progressPercent() }}% часу публікації минуло</p>
        </div>
    @endif

    {{-- Кнопки дій --}}
    <div class="flex flex-col gap-2">
        @if($vacancy->status === VacancyStatus::Active || $vacancy->status === VacancyStatus::Expired)
            <button
                wire:click="$dispatch('open-extend-modal', { id: {{ $vacancy->id }} })"
                class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md text-sm font-medium
                    {{ $this->isCritical()
                        ? 'bg-red-600 hover:bg-red-700 text-white animate-pulse'
                        : 'bg-blue-600 hover:bg-blue-700 text-white' }}"
            >
                {{ $vacancy->status === VacancyStatus::Expired ? 'Поновити публікацію' : 'Продовжити публікацію' }}
            </button>

            @if($vacancy->status === VacancyStatus::Active)
                <button
                    wire:click="$dispatch('open-archive-modal', { id: {{ $vacancy->id }} })"
                    class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md text-sm font-medium border border-gray-300 hover:bg-gray-50 text-gray-700"
                >
                    Архівувати
                </button>
            @endif

        @elseif($vacancy->status === VacancyStatus::Draft)
            <button
                wire:click="$dispatch('open-publish-modal', { id: {{ $vacancy->id }} })"
                class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white"
            >
                Опублікувати
            </button>

        @elseif($vacancy->status === VacancyStatus::Archived)
            <button
                wire:click="$dispatch('open-extend-modal', { id: {{ $vacancy->id }} })"
                class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md text-sm font-medium border border-gray-300 hover:bg-gray-50 text-gray-700"
            >
                Відновити
            </button>
        @endif
    </div>
</div>
