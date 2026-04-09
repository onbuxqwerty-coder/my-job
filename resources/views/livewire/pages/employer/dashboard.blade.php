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

<div class="min-h-screen bg-gray-50">
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
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-900">Мої вакансії</h2>
                </div>

                @if($this->vacancies->isEmpty())
                    <div class="p-12 text-center text-gray-400">
                        <p>Вакансій ще немає. <a href="{{ route('employer.vacancies.create') }}" class="text-blue-600 hover:underline">Додати першу вакансію →</a></p>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Назва</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Статус</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Відгуки</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Опубліковано</th>
                                <th class="text-right px-6 py-3 w-8"></th>
                            </tr>
                        </thead>
                        @foreach($this->vacancies as $vacancy)
                                <tbody x-data="{ open: false }" class="divide-y divide-gray-100">
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click="open = !open">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-gray-900">{{ $vacancy->title }}</p>
                                        <p class="text-xs text-gray-400">{{ $vacancy->employment_type->label() }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($vacancy->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Активна</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Чернетка</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4" @click.stop>
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
                                    <td class="px-6 py-4 text-right">
                                        <svg class="w-4 h-4 text-gray-400 inline transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </td>
                                </tr>
                                {{-- Expanded row --}}
                                <tr x-show="open" x-transition style="display:none;">
                                    <td colspan="5" class="px-6 pb-5 pt-0 bg-gray-50">
                                        <div style="border-top:1px solid #e5e7eb; padding-top:16px; display:flex; gap:24px; align-items:flex-start;">
                                            {{-- Description preview --}}
                                            <div style="flex:1; font-size:13px; color:#6b7280; line-height:1.6;">
                                                {{ Str::limit($vacancy->description, 300) }}
                                                @if($vacancy->salary_from)
                                                    <span style="display:inline-block; margin-top:8px; padding:2px 10px; background:#f0fdf4; color:#16a34a; border-radius:999px; font-size:12px; font-weight:600;">
                                                        {{ number_format($vacancy->salary_from) }}@if($vacancy->salary_to)–{{ number_format($vacancy->salary_to) }}@endif {{ $vacancy->currency }}
                                                    </span>
                                                @endif
                                            </div>
                                            {{-- Actions --}}
                                            <div style="display:flex; flex-direction:column; gap:8px; flex-shrink:0; align-items:flex-end;">
                                                <a href="{{ route('employer.vacancies.edit', $vacancy->id) }}"
                                                   style="font-size:13px; font-weight:600; color:#2563eb; text-decoration:none; padding:6px 14px; border:1px solid #2563eb; border-radius:8px; white-space:nowrap;"
                                                   @click.stop>
                                                    Редагувати
                                                </a>

                                                <button wire:click="toggleActive({{ $vacancy->id }})"
                                                        style="font-size:13px; font-weight:600; padding:6px 14px; border-radius:8px; border:1px solid; cursor:pointer; background:transparent; white-space:nowrap;
                                                               {{ $vacancy->is_active ? 'color:#ca8a04; border-color:#ca8a04;' : 'color:#16a34a; border-color:#16a34a;' }}"
                                                        @click.stop>
                                                    {{ $vacancy->is_active ? 'Деактивувати' : 'Активувати' }}
                                                </button>

                                                @if(!$vacancy->is_featured)
                                                    <a href="{{ route('employer.vacancies.promote', $vacancy->id) }}"
                                                       style="font-size:13px; font-weight:600; color:#d97706; text-decoration:none; padding:6px 14px; border:1px solid #d97706; border-radius:8px; white-space:nowrap;"
                                                       @click.stop>
                                                        Просувати
                                                    </a>
                                                @else
                                                    <span style="font-size:13px; font-weight:600; color:#d97706; padding:6px 14px; border:1px solid #fde68a; border-radius:8px; background:#fffbeb;">
                                                        В топі до {{ $vacancy->featured_until?->format('d.m.Y') }}
                                                    </span>
                                                @endif

                                                <button wire:click="delete({{ $vacancy->id }})"
                                                        wire:confirm="Ви впевнені, що хочете видалити цю вакансію?"
                                                        style="font-size:13px; font-weight:600; color:#dc2626; padding:6px 14px; border:1px solid #dc2626; border-radius:8px; background:transparent; cursor:pointer; white-space:nowrap;"
                                                        @click.stop>
                                                    Видалити
                                                </button>
                                            </div>
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
</div>
