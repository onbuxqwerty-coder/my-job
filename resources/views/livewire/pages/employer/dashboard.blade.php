<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function toggleActive(int $vacancyId): void
    {
        $vacancy = Vacancy::where('company_id', auth()->user()->company->id)
            ->findOrFail($vacancyId);

        $vacancy->update(['is_active' => !$vacancy->is_active]);
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
        return $this->vacancies->where('is_active', true)->count();
    }

    #[Computed]
    public function totalApplications(): int
    {
        return $this->vacancies->sum('applications_count');
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
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
                        @foreach($this->vacancies as $vacancy)
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr class="vacancy-row" onclick="window.location='{{ route('jobs.show', $vacancy) }}'" style="cursor:pointer;">
                                    <td class="px-6 py-4">
                                        <p class="vacancy-title font-medium text-gray-900 dark:text-gray-100" style="transition: color .2s, transform .2s;">{{ $vacancy->title }}</p>
                                        <p class="text-xs text-gray-400">{{ implode(', ', array_map(fn($t) => \App\Enums\EmploymentType::from($t)->label(), (array) $vacancy->employment_type)) }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
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
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        {{ $vacancy->city?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        @if($vacancy->is_featured)
                                            <span title="Гаряча вакансія" style="font-size:18px; line-height:1;">🔥</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        @if($vacancy->is_top)
                                            <span title="Топ вакансія" style="font-size:18px; line-height:1;">⭐</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4" onclick="event.stopPropagation()">
                                        <a href="{{ route('employer.applicants', $vacancy->id) }}"
                                           class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:text-blue-800">
                                            {{ $vacancy->applications_count }}
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-400">
                                        {{ $vacancy->created_at->format('d.m.Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                                        <div style="display:flex; gap:12px; justify-content:flex-end; align-items:center;">
                                            {{-- Toggle активності --}}
                                            <button wire:click="toggleActive({{ $vacancy->id }})"
                                                    title="{{ $vacancy->is_active ? 'Деактивувати' : 'Активувати' }}"
                                                    style="position:relative; display:inline-flex; align-items:center; width:44px; height:24px; border-radius:999px; border:none; cursor:pointer; transition:background .25s; background:{{ $vacancy->is_active ? '#16a34a' : '#d1d5db' }}; flex-shrink:0;">
                                                <span style="position:absolute; left:{{ $vacancy->is_active ? '22px' : '2px' }}; width:20px; height:20px; border-radius:50%; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.2); transition:left .25s;"></span>
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
                                            <button wire:click="delete({{ $vacancy->id }})"
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
                                </tbody>
                            @endforeach
                    </table>
                @endif
            </div>
        @endif
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
