<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\Vacancy;
use App\Models\VacancyRecommendation;
use App\Services\RecommendationService;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public int $vacancyId;

    public function mount(int $vacancyId): void
    {
        $this->vacancyId = $vacancyId;

        abort_unless(
            Vacancy::whereHas('company', fn ($q) => $q->where('user_id', auth()->id()))
                ->where('id', $vacancyId)
                ->exists(),
            403
        );
    }

    #[Computed]
    public function recommendations(): \Illuminate\Support\Collection
    {
        return VacancyRecommendation::where('vacancy_id', $this->vacancyId)
            ->with(['user.candidateSkills'])
            ->orderByDesc('score')
            ->get();
    }

    #[Computed]
    public function vacancySkillIds(): array
    {
        return Vacancy::find($this->vacancyId)?->skills()->pluck('skill_tags.id')->all() ?? [];
    }

    public function invite(int $candidateId): void
    {
        $vacancy = Vacancy::find($this->vacancyId);

        if (! $vacancy) {
            return;
        }

        $exists = Application::where('vacancy_id', $this->vacancyId)
            ->where('user_id', $candidateId)
            ->exists();

        if (! $exists) {
            Application::create([
                'vacancy_id'  => $this->vacancyId,
                'user_id'     => $candidateId,
                'resume_url'  => '',
                'cover_letter' => 'Запрошення від роботодавця',
                'status'      => \App\Enums\ApplicationStatus::Reviewing,
            ]);
        }
    }
}; ?>

<div class="space-y-4">
    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
        🤖 Рекомендовані кандидати
    </h2>

    @if($this->recommendations->isEmpty())
        <div class="text-center py-10 text-gray-500 dark:text-gray-400">
            <p>Поки що немає рекомендованих кандидатів</p>
            <p class="text-sm mt-1">Рекомендації формуються автоматично на основі навичок</p>
        </div>
    @else
        @foreach($this->recommendations as $rec)
            @php
                $candidate    = $rec->user;
                $score        = $rec->score;
                $skillIds     = $this->vacancySkillIds;
                $candSkillIds = $candidate->candidateSkills->pluck('id')->all();
            @endphp

            <div class="mj-card rounded-xl p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 space-y-2">
                        {{-- Ім'я кандидата --}}
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $candidate->name }}
                        </p>

                        {{-- Score-бар --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium
                                    {{ $score >= 90 ? 'text-green-600 dark:text-green-400' : ($score >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500') }}">
                                    @if($score >= 90) Відмінний збіг
                                    @elseif($score >= 70) Хороший збіг
                                    @else Частковий збіг
                                    @endif
                                </span>
                                <span class="text-gray-400">{{ $score }}%</span>
                            </div>
                            <div class="h-1.5 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full
                                    {{ $score >= 90 ? 'bg-green-500' : ($score >= 70 ? 'bg-yellow-400' : 'bg-gray-400') }}"
                                     style="width: {{ $score }}%">
                                </div>
                            </div>
                        </div>

                        {{-- Навички кандидата --}}
                        @if($candidate->candidateSkills->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($candidate->candidateSkills as $skill)
                                    @php $needed = in_array($skill->id, $skillIds); @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                        {{ $needed ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                                        @if($needed) ✓ @endif
                                        {{ $skill->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Кнопка Запросити --}}
                    <button type="button" wire:click="invite({{ $candidate->id }})"
                            class="shrink-0 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold rounded-lg transition">
                        Запросити
                    </button>
                </div>
            </div>
        @endforeach
    @endif
</div>
