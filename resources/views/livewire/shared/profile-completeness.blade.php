<?php

declare(strict_types=1);

use App\Models\Vacancy;
use App\Services\ProfileCompletenessService;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public string $type    = 'candidate';
    public ?int   $modelId = null;

    #[Computed]
    public function result(): array
    {
        $service = app(ProfileCompletenessService::class);

        return match ($this->type) {
            'employer' => $service->employerScore(auth()->user()),
            'vacancy'  => $service->vacancyScore(Vacancy::findOrFail($this->modelId)),
            default    => $service->candidateScore(auth()->user()),
        };
    }
}; ?>

@php
    $result    = $this->result;
    $score     = $result['score'];
    $nextStep  = $result['next_step'];
    $missing   = $result['missing'];

    $barColor = match (true) {
        $score >= 75 => '#16a34a',
        $score >= 40 => '#d97706',
        default      => '#dc2626',
    };
@endphp

<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 space-y-4">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Заповненість профілю</h3>

    {{-- Progress bar --}}
    <div>
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $score }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
            <div
                class="h-2.5 rounded-full transition-all duration-500"
                style="width: {{ $score }}%; background-color: {{ $barColor }};"
            ></div>
        </div>
    </div>

    @if($score >= 100)
        <div class="flex items-center gap-2 text-green-700 dark:text-green-400 text-sm font-medium">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Профіль заповнений повністю
        </div>
    @else
        {{-- Next step --}}
        @if($nextStep)
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3">
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Наступний крок:</p>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $nextStep['label'] }}</span>
                    <a
                        href="{{ $nextStep['url'] }}"
                        class="shrink-0 text-xs font-medium text-white px-3 py-1 rounded-lg"
                        style="background-color: #F36F21;"
                    >
                        Заповнити
                    </a>
                </div>
            </div>
        @endif

        {{-- Missing fields collapsible --}}
        @if(count($missing) > 0)
            <div x-data="{ open: false }">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center justify-between w-full text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                >
                    <span>Що ще бракує</span>
                    <svg
                        class="w-4 h-4 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <ul x-show="open" x-transition class="mt-2 space-y-1">
                    @foreach($missing as $item)
                        <li class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 py-0.5">
                            <span>· {{ $item['label'] }}</span>
                            <span class="text-gray-400 dark:text-gray-500">{{ $item['weight'] }} б.</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
