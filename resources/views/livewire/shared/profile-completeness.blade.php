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

<div class="w-full min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2.5 flex items-center gap-3">
    {{-- Label + bar --}}
    <div class="flex items-center gap-2 shrink-0">
        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">Заповненість профілю</span>
        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 shrink-0">
            <div class="h-1.5 rounded-full transition-all duration-500" style="width:{{ $score }}%; background-color:{{ $barColor }};"></div>
        </div>
        <span class="text-xs font-medium shrink-0" style="color:{{ $barColor }};">{{ $score }}%</span>
    </div>

    @if($score < 100 && $nextStep)
        <div class="w-px h-4 bg-gray-200 dark:bg-gray-600 shrink-0"></div>
        {{-- Next step --}}
        <span class="text-xs text-gray-500 dark:text-gray-400 truncate min-w-0">
            <span class="font-medium text-gray-700 dark:text-gray-300">Наступний крок:</span>
            {{ $nextStep['label'] }}
        </span>
        <a href="{{ $nextStep['url'] }}"
           class="shrink-0 text-xs font-semibold text-white px-3 py-1 rounded-lg ml-auto"
           style="background-color:#F36F21;">
            Заповнити
        </a>
    @elseif($score >= 100)
        <div class="w-px h-4 bg-gray-200 dark:bg-gray-600 shrink-0"></div>
        <span class="text-xs font-medium text-green-600 dark:text-green-400 whitespace-nowrap">Профіль заповнений ✓</span>
    @endif
</div>
