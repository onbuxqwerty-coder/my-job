<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public bool $showProfileModal = false;

    #[On('open-profile-modal')]
    public function openProfileModal(): void
    {
        $this->showProfileModal = true;
    }

    public function toggleActive(int $vacancyId): void
    {
        $vacancy = Vacancy::where('company_id', auth()->user()->company->id)
            ->findOrFail($vacancyId);

        if ($vacancy->status === \App\Enums\VacancyStatus::Active) {
            $vacancy->forceFill(['status' => \App\Enums\VacancyStatus::Draft, 'is_active' => false])->save();
            return;
        }

        if (!auth()->user()->company->isProfileComplete()) {
            $this->showProfileModal = true;
            return;
        }

        $vacancy->publish();
    }

    public function delete(int $vacancyId): void
    {
        Vacancy::where('company_id', auth()->user()->company->id)
            ->findOrFail($vacancyId)
            ->delete();
    }

    #[Computed]
    public function vacancies(): \Illuminate\Database\Eloquent\Collection
    {
        return Vacancy::withCount('applications')
            ->with('city')
            ->where('company_id', auth()->user()->company?->id)
            ->latest()
            ->get();
    }

    #[Computed]
    public function company(): ?\App\Models\Company
    {
        return auth()->user()->company;
    }

    #[Computed]
    public function totalVacancies(): int
    {
        return $this->vacancies->count();
    }

    #[Computed]
    public function activeVacancies(): int
    {
        return $this->vacancies->where('status', \App\Enums\VacancyStatus::Active)->count();
    }

    #[Computed]
    public function totalApplications(): int
    {
        return $this->vacancies->sum('applications_count');
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900" wire:poll.60000ms>
    <x-employer-tabs />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


        @if(!$this->company)
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                <p class="text-yellow-800 font-medium">Ви ще не налаштували профіль компанії.</p>
                <a href="{{ route('employer.profile') }}" class="mt-3 inline-block text-sm text-blue-600 hover:underline">
                    Налаштувати профіль →
                </a>
            </div>
        @else

            <livewire:shared.profile-completeness type="employer" />

            {{-- Vacancies table --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border employer-card-border dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Мої вакансії</h2>
                </div>

                @if($this->vacancies->isEmpty())
                    <div class="p-12 text-center text-gray-400 dark:text-gray-500">
                        <p>Вакансій ще немає. <a href="{{ route('employer.vacancies.create') }}" class="text-blue-600 hover:underline">Додати першу вакансію →</a></p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Назва</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Зарплата</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Місто</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">HOT</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">TOP</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Відгуки</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Опубліковано</th>
                                <th class="text-right px-6 py-3 w-8"></th>
                            </tr>
                        </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($this->vacancies as $vacancy)
                                <tr wire:key="vacancy-{{ $vacancy->id }}" class="vacancy-row">
                                    @php $goTo = route('jobs.show', $vacancy); @endphp
                                    <td class="px-6 py-4 row-link" onclick="window.location='{{ $goTo }}'" style="cursor:pointer;">
                                        <p class="vacancy-title font-medium text-gray-900 dark:text-gray-100" style="transition: color .2s, transform .2s;">{{ $vacancy->title }}</p>
                                        <p class="text-xs text-gray-400">{{ implode(', ', array_map(fn($t) => \App\Enums\EmploymentType::from($t)->label(), (array) $vacancy->employment_type)) }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300 row-link" onclick="window.location='{{ $goTo }}'" style="cursor:pointer;">
                                        @if($vacancy->salary_from || $vacancy->salary_to)
                                            <span class="font-medium">
                                                @if($vacancy->salary_from && $vacancy->salary_to)
                                                    {{ number_format($vacancy->salary_from, 0, '.', ' ') }} – {{ number_format($vacancy->salary_to, 0, '.', ' ') }}
                                                @elseif($vacancy->salary_from)
                                                    від {{ number_format($vacancy->salary_from, 0, '.', ' ') }}
                                                @else
                                                    до {{ number_format($vacancy->salary_to, 0, '.', ' ') }}
                                                @endif
                                            </span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $vacancy->currency }}</span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300 row-link" onclick="window.location='{{ $goTo }}'" style="cursor:pointer;">
                                        {{ $vacancy->city?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-4 text-center row-link" onclick="window.location='{{ $goTo }}'" style="cursor:pointer;">
                                        @if($vacancy->is_featured)
                                            <span title="Гаряча вакансія" style="font-size:18px; line-height:1;">🔥</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center row-link" onclick="window.location='{{ $goTo }}'" style="cursor:pointer;">
                                        @if($vacancy->is_top)
                                            <span title="Топ вакансія" style="font-size:18px; line-height:1;">⭐</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('employer.applicants', $vacancy->id) }}"
                                           class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:text-blue-800">
                                            {{ $vacancy->applications_count }}
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-400 row-link" onclick="window.location='{{ $goTo }}'" style="cursor:pointer;">
                                        {{ $vacancy->created_at->format('d.m.Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div style="display:flex; gap:12px; justify-content:flex-end; align-items:center;">
                                            {{-- Toggle активності --}}
                                            @php $isActive = $vacancy->status === \App\Enums\VacancyStatus::Active; @endphp
                                            <button type="button"
                                                    wire:click="toggleActive({{ $vacancy->id }})"
                                                    title="{{ $isActive ? 'Деактивувати' : 'Активувати' }}"
                                                    style="position:relative; display:inline-flex; align-items:center; width:44px; height:24px; border-radius:999px; border:none; cursor:pointer; transition:background .25s; background:{{ $isActive ? '#16a34a' : '#d1d5db' }}; flex-shrink:0;">
                                                <span style="position:absolute; left:{{ $isActive ? '22px' : '2px' }}; width:20px; height:20px; border-radius:50%; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.2); transition:left .25s;"></span>
                                            </button>

                                            {{-- Редагувати --}}
                                            <a href="{{ route('employer.vacancies.edit', $vacancy->id) }}"
                                               title="Редагувати"
                                               style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; transition:opacity .15s;"
                                               onmouseover="this.style.opacity='.7'"
                                               onmouseout="this.style.opacity='1'">
                                                <img src="{{ asset('img/edit.webp') }}" alt="Редагувати" style="width:24px; height:24px; object-fit:contain;">
                                            </a>

                                            {{-- Просувати / В топі --}}
                                            @if(!$vacancy->is_featured)
                                                <a href="{{ route('employer.vacancies.promote', $vacancy->id) }}"
                                                   title="Просувати"
                                                   style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; transition:opacity .15s;"
                                                   onmouseover="this.style.opacity='.7'"
                                                   onmouseout="this.style.opacity='1'">
                                                    <img src="{{ asset('img/seo.webp') }}" alt="Просувати" style="width:24px; height:24px; object-fit:contain;">
                                                </a>
                                            @else
                                                <span title="В топі до {{ $vacancy->featured_until?->format('d.m.Y') }}"
                                                      style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; opacity:.4; cursor:default;">
                                                    <img src="{{ asset('img/seo.webp') }}" alt="В топі" style="width:24px; height:24px; object-fit:contain;">
                                                </span>
                                            @endif

                                            {{-- Видалити --}}
                                            <button type="button"
                                                    wire:click="delete({{ $vacancy->id }})"
                                                    wire:confirm="Ви впевнені, що хочете видалити цю вакансію?"
                                                    title="Видалити"
                                                    style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:none; background:transparent; cursor:pointer; transition:opacity .15s;"
                                                    onmouseover="this.style.opacity='.7'"
                                                    onmouseout="this.style.opacity='1'">
                                                <img src="{{ asset('img/delete.webp') }}" alt="Видалити" style="width:24px; height:24px; object-fit:contain;">
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>{{-- overflow-x-auto --}}
                @endif
            </div>
        @endif
    </div>

    {{-- Modal #9: профіль не заповнений --}}
    <div
        x-data="{ show: $wire.entangle('showProfileModal') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none;"
    >
        <div class="absolute inset-0 bg-black/60" @click="show = false"></div>
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center"
            style="display:none;"
        >
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Вакансія не активована</h2>
            <p class="text-gray-500 mb-1">Спочатку заповніть профіль компанії</p>
            <div class="my-6 h-px bg-gray-100"></div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-left">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div>
                        <p class="text-amber-800 font-semibold text-sm">Вакансія активна 1 добу</p>
                        <p class="text-amber-700 text-sm mt-0.5">
                            Щоб вакансія залишалась активною <strong>30 діб</strong> — заповніть профіль компанії. Це займе 1–2 хвилини.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-3">
                <a href="{{ route('employer.profile') }}"
                   class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition text-center">
                    Заповнити профіль компанії
                </a>
                <button type="button" @click="show = false"
                        class="w-full py-2 text-sm text-gray-400 hover:text-gray-600 transition">
                    Пропустити
                </button>
            </div>
        </div>
    </div>

    <style>
    .vacancy-row {
        transition: background .2s, box-shadow .2s, transform .2s;
        cursor: pointer;
    }
    .vacancy-row:hover {
        background: #f0f7ff;
        box-shadow: 0 4px 16px rgba(37,99,235,.12), inset 0 0 0 2px #2563eb;
        transform: translateY(-2px);
    }
    .vacancy-row:hover .vacancy-title { color: #2563eb; }

    [data-theme="dark"] .vacancy-row:hover,
    .dark .vacancy-row:hover {
        background: transparent;
        box-shadow: none;
        transform: translateY(-2px);
    }
    [data-theme="dark"] .vacancy-row:hover .vacancy-title,
    .dark .vacancy-row:hover .vacancy-title {
        color: inherit;
    }
    </style>
</div>
