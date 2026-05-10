<?php

declare(strict_types=1);

use App\Models\VacancyRecommendation;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    #[Computed]
    public function recommendations(): \Illuminate\Support\Collection
    {
        return VacancyRecommendation::where('user_id', auth()->id())
            ->with(['vacancy.company', 'vacancy.city', 'vacancy.skills'])
            ->orderByDesc('score')
            ->get();
    }

    #[Computed]
    public function candidateSkillIds(): array
    {
        return auth()->user()->candidateSkills()->pluck('skill_tags.id')->all();
    }
}; ?>

<div class="space-y-4">
    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
        🎯 Рекомендовано для вас
    </h2>

    @if($this->recommendations->isEmpty())
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
            <p class="text-lg">Заповніть профіль, щоб отримати рекомендації</p>
            <p class="text-sm mt-1">Додайте навички, місто та очікувану зарплату</p>
        </div>
    @else
        @foreach($this->recommendations as $rec)
            @php
                $vacancy      = $rec->vacancy;
                $score        = $rec->score;
                $skillIds     = $this->candidateSkillIds;
                $reqSkills    = $vacancy->skills->where('pivot.is_required', true);
                $missingSkills = $reqSkills->filter(fn($s) => ! in_array($s->id, $skillIds));
            @endphp

            <div class="mj-card rounded-xl p-5 space-y-3">
                {{-- Заголовок вакансії --}}
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <a href="{{ route('jobs.show', $vacancy->slug) }}"
                           class="text-base font-semibold text-gray-900 dark:text-white hover:text-orange-500 transition">
                            {{ $vacancy->title }}
                        </a>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $vacancy->company->name }}
                            @if($vacancy->city)
                                · {{ $vacancy->city->name }}
                            @else
                                · Remote
                            @endif
                            @if($vacancy->salary_from)
                                · від {{ number_format($vacancy->salary_from, 0, '.', ' ') }} грн
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('jobs.show', $vacancy->slug) }}"
                       class="shrink-0 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold rounded-lg transition">
                        Відгукнутись
                    </a>
                </div>

                {{-- Score-бар --}}
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium
                            {{ $score >= 90 ? 'text-green-600 dark:text-green-400' : ($score >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500 dark:text-gray-400') }}">
                            @if($score >= 90) Відмінний збіг
                            @elseif($score >= 70) Хороший збіг
                            @else Частковий збіг
                            @endif
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $score }}%</span>
                    </div>
                    <div class="h-1.5 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all
                            {{ $score >= 90 ? 'bg-green-500' : ($score >= 70 ? 'bg-yellow-400' : 'bg-gray-400') }}"
                             style="width: {{ $score }}%">
                        </div>
                    </div>
                </div>

                {{-- Навички --}}
                @if($vacancy->skills->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach($vacancy->skills as $skill)
                            @php $has = in_array($skill->id, $skillIds); @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $has ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' }}">
                                {{ $has ? '✓' : '✗' }}
                                {{ $skill->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                {{-- Відсутні обов'язкові навички --}}
                @if($missingSkills->isNotEmpty())
                    <p class="text-xs text-amber-600 dark:text-amber-400">
                        ⚠ Бракує: {{ $missingSkills->pluck('name')->implode(', ') }}
                    </p>
                @endif
            </div>
        @endforeach
    @endif
</div>
