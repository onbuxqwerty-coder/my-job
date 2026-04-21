<?php

declare(strict_types=1);

use App\Models\Resume;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Resume $resume;

    public function mount(Resume $resume): void
    {
        $isOwner = auth()->check() && auth()->id() === $resume->user_id;

        if ($resume->status !== 'published' && !$isOwner) {
            abort(404);
        }

        $this->resume = $resume;
    }

    public function toggleStatus(): void
    {
        abort_unless(auth()->id() === $this->resume->user_id, 403);

        $this->resume->update([
            'status' => $this->resume->status === 'published' ? 'draft' : 'published',
        ]);

        $this->resume->refresh();
    }

    public function deleteResume(): void
    {
        abort_unless(auth()->id() === $this->resume->user_id, 403);

        $this->resume->delete();
        $this->redirect(route('seeker.resumes'), navigate: true);
    }
}; ?>

@php
    $info       = $resume->personal_info ?? [];
    $location   = $resume->location ?? [];
    $name       = trim(($info['first_name'] ?? '') . ' ' . ($info['last_name'] ?? ''));
    $position   = $info['position'] ?? null;
    $email      = $info['email'] ?? null;
    $phone      = $info['phone'] ?? null;
    $about      = $info['about'] ?? null;
    $city       = $location['city'] ?? null;
    $remote     = $location['remote'] ?? false;
    $isOwner    = auth()->check() && auth()->id() === $resume->user_id;
    $isPublished = $resume->status === 'published';
@endphp

<div class="max-w-3xl mx-auto px-4 py-10 sm:px-6">

    {{-- Owner actions --}}
    @if ($isOwner)
        <div class="flex flex-wrap items-center gap-2 mb-6">
            <a href="{{ route('seeker.resumes') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Мої резюме
            </a>

            <a href="{{ route('resumes.edit', $resume->id) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Редагувати
            </a>

            <button
                wire:click="toggleStatus"
                wire:confirm="{{ $isPublished ? 'Перевести резюме в чернетку?' : 'Опублікувати резюме?' }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition
                       {{ $isPublished
                           ? 'bg-amber-100 text-amber-700 hover:bg-amber-200'
                           : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                @if ($isPublished)
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                    В чернетку
                @else
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Активувати
                @endif
            </button>

            <button
                wire:click="deleteResume"
                wire:confirm="Видалити резюме «{{ $name }}»? Цю дію не можна відмінити."
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-red-100 text-red-700 hover:bg-red-200 shadow-sm transition ml-auto">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Видалити
            </button>
        </div>

        {{-- Draft banner --}}
        @if (!$isPublished)
            <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm mb-6">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Це чернетка — видима лише вам
            </div>
        @endif
    @endif

    {{-- Resume header --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-8 py-8 mb-6">
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-2xl shrink-0">
                {{ mb_strtoupper(mb_substr($name ?: '?', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-2xl font-bold text-gray-900">{{ $name ?: 'Резюме' }}</h1>
                @if ($position)
                    <p class="text-base text-gray-600 mt-1">{{ $position }}</p>
                @endif
                <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-500">
                    @if ($city)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $city }}
                        </span>
                    @endif
                    @if ($remote)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Дистанційно
                        </span>
                    @endif
                    @if ($email)
                        <span>{{ $email }}</span>
                    @endif
                    @if ($phone)
                        <span>{{ $phone }}</span>
                    @endif
                </div>
            </div>
        </div>

        @if ($about)
            <div class="mt-6 pt-6 border-t border-gray-100">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Про себе</h2>
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">{{ $about }}</p>
            </div>
        @endif
    </div>

    {{-- Experience --}}
    @if ($resume->experiences->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-8 py-6 mb-6">
            <h2 class="text-base font-bold text-gray-900 mb-4">Досвід роботи</h2>
            <div class="space-y-5">
                @foreach ($resume->experiences()->orderByDesc('start_date')->get() as $exp)
                    <div class="flex gap-4">
                        <div class="w-2 h-2 rounded-full bg-blue-500 mt-2 shrink-0"></div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">{{ $exp->position }}</p>
                            <p class="text-sm text-gray-600">{{ $exp->company_name }}
                                @if ($exp->company_industry)
                                    · {{ $exp->company_industry }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ \Carbon\Carbon::parse($exp->start_date)->format('m.Y') }} —
                                {{ $exp->is_current ? 'по теперішній час' : ($exp->end_date ? \Carbon\Carbon::parse($exp->end_date)->format('m.Y') : '') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Skills --}}
    @if ($resume->skills->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-8 py-6">
            <h2 class="text-base font-bold text-gray-900 mb-4">Навички</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($resume->skills()->orderBy('skill_name')->get() as $skill)
                    <span class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-lg">
                        {{ $skill->skill_name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

</div>
