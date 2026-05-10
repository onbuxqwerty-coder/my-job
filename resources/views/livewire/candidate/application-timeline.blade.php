<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Services\ApplicationStatusService;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public int  $applicationId;
    public bool $confirmWithdraw = false;

    public function mount(int $applicationId): void
    {
        $this->applicationId = $applicationId;

        abort_unless(
            Application::where('id', $applicationId)->where('user_id', auth()->id())->exists(),
            403
        );
    }

    #[Computed]
    public function application(): Application
    {
        return Application::with(['vacancy.company', 'statusLogs.changedBy'])
            ->where('user_id', auth()->id())
            ->findOrFail($this->applicationId);
    }

    /** @return array<ApplicationStatus> */
    public function timelineSteps(): array
    {
        return [
            ApplicationStatus::Pending,
            ApplicationStatus::Viewed,
            ApplicationStatus::Reviewing,
            ApplicationStatus::Interview,
            ApplicationStatus::Offered,
        ];
    }

    public function isStepReached(ApplicationStatus $step): bool
    {
        $order = [
            ApplicationStatus::Pending->value   => 1,
            ApplicationStatus::Viewed->value    => 2,
            ApplicationStatus::Reviewing->value => 3,
            ApplicationStatus::Screening->value => 3,
            ApplicationStatus::Interview->value => 4,
            ApplicationStatus::Offered->value   => 5,
            ApplicationStatus::Hired->value     => 5,
            ApplicationStatus::Rejected->value  => 5,
        ];

        $current = $order[$this->application->status->value] ?? 0;
        $target  = $order[$step->value] ?? 99;

        return $current >= $target;
    }

    public function getStepDate(ApplicationStatus $step): ?string
    {
        $log = $this->application->statusLogs
            ->sortBy('created_at')
            ->firstWhere('status', $step);

        return $log?->created_at?->format('d.m.Y');
    }

    public function canWithdraw(): bool
    {
        return in_array($this->application->status, [
            ApplicationStatus::Pending,
            ApplicationStatus::Viewed,
        ], true);
    }

    public function withdraw(): void
    {
        if (! $this->canWithdraw()) {
            return;
        }

        try {
            app(ApplicationStatusService::class)->changeStatus(
                $this->application,
                ApplicationStatus::Withdrawn,
                auth()->user(),
                'seeker',
            );
            $this->confirmWithdraw = false;
            unset($this->application);
        } catch (\Throwable) {
            // Статус вже встановлено або дія заборонена
        }
    }
}; ?>

<div class="space-y-6">
    {{-- Заголовок --}}
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $this->application->vacancy->title }}
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $this->application->vacancy->company->name }}
        </p>
    </div>

    {{-- Відмовлено / Відкликано --}}
    @if(in_array($this->application->status, [ApplicationStatus::Rejected, ApplicationStatus::Withdrawn], true))
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg
            {{ $this->application->status === ApplicationStatus::Rejected ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="{{ $this->application->status === ApplicationStatus::Rejected ? 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' }}"/>
            </svg>
            <span class="font-medium">{{ $this->application->status->label() }}</span>
        </div>
    @endif

    {{-- Горизонтальний таймлайн --}}
    <div class="relative">
        <div class="flex items-start justify-between">
            @foreach($this->timelineSteps() as $step)
                @php $reached = $this->isStepReached($step); $date = $this->getStepDate($step); @endphp
                <div class="flex flex-col items-center flex-1 relative">
                    {{-- Лінія між кроками --}}
                    @if(! $loop->last)
                        <div class="absolute top-3 left-1/2 w-full h-0.5
                            {{ $reached ? 'bg-orange-400' : 'bg-gray-300 dark:bg-gray-600' }}"
                             style="transform: translateX(50%)">
                        </div>
                    @endif

                    {{-- Круг --}}
                    <div class="relative z-10 flex items-center justify-center w-7 h-7 rounded-full border-2
                        {{ $reached
                            ? 'bg-orange-400 border-orange-400 text-white'
                            : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-400' }}">
                        @if($reached)
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                        @endif
                    </div>

                    {{-- Підпис --}}
                    <div class="mt-2 text-center">
                        <p class="text-xs font-medium {{ $reached ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                            {{ $step->label() }}
                        </p>
                        @if($date)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $date }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Лог статусів --}}
    @if($this->application->statusLogs->isNotEmpty())
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Історія</h4>
            @foreach($this->application->statusLogs->sortBy('created_at') as $log)
                <div class="flex items-start gap-3 text-sm">
                    <span class="text-gray-400 dark:text-gray-500 whitespace-nowrap">
                        {{ $log->created_at?->format('d.m.Y H:i') }}
                    </span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        {{ $log->status->label() }}
                    </span>
                    @if($log->comment)
                        <span class="text-gray-500 dark:text-gray-400">— {{ $log->comment }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Кнопка відкликання --}}
    @if($this->canWithdraw())
        @if($confirmWithdraw)
            <div class="flex items-center gap-3 pt-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Відкликати відгук?</span>
                <button type="button" wire:click="withdraw"
                        class="px-4 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium">
                    Так, відкликати
                </button>
                <button type="button" wire:click="$set('confirmWithdraw', false)"
                        class="px-4 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Скасувати
                </button>
            </div>
        @else
            <button type="button" wire:click="$set('confirmWithdraw', true)"
                    class="text-sm text-red-500 hover:text-red-700 dark:hover:text-red-400 transition underline">
                Відкликати відгук
            </button>
        @endif
    @endif
</div>
