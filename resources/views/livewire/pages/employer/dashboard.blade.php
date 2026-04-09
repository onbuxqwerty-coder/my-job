<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\Vacancy;
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

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Кабінет роботодавця</h1>
                @if($this->company)
                    <p class="text-gray-500 text-sm mt-1">{{ $this->company->name }}</p>
                @endif
            </div>
            <div class="flex gap-3">
                <a href="{{ route('employer.candidates') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">
                    Кандидати
                </a>
                <a href="{{ route('employer.message.templates') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">
                    Шаблони
                </a>
                <a href="{{ route('employer.analytics') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">
                    Аналітика
                </a>
                <a href="{{ route('employer.profile') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">
                    Профіль
                </a>
                <a href="{{ route('employer.vacancies.create') }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700">
                    + Додати вакансію
                </a>
            </div>
        </div>

        @if(!$this->company)
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                <p class="text-yellow-800 font-medium">Ви ще не налаштували профіль компанії.</p>
                <a href="{{ route('employer.profile') }}" class="mt-3 inline-block text-sm text-blue-600 hover:underline">
                    Налаштувати профіль →
                </a>
            </div>
        @else

            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Всього вакансій</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $this->totalVacancies }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Активних</p>
                    <p class="text-3xl font-bold text-green-600">{{ $this->activeVacancies }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Відгуків</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $this->totalApplications }}</p>
                </div>
            </div>

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
                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Дії</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($this->vacancies as $vacancy)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-gray-900">{{ $vacancy->title }}</p>
                                        <p class="text-xs text-gray-400 capitalize">{{ str_replace('-', ' ', $vacancy->employment_type->value) }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($vacancy->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Активна</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Чернетка</span>
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
                                    <td class="px-6 py-4 text-gray-400">
                                        {{ $vacancy->created_at->format('d.m.Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('employer.vacancies.edit', $vacancy->id) }}"
                                               class="text-xs text-gray-600 hover:text-blue-600 font-medium">Редагувати</a>

                                            <button wire:click="toggleActive({{ $vacancy->id }})"
                                                    class="text-xs {{ $vacancy->is_active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }} font-medium">
                                                {{ $vacancy->is_active ? 'Деактивувати' : 'Активувати' }}
                                            </button>

                                            @if(!$vacancy->is_featured)
                                                <a href="{{ route('employer.vacancies.promote', $vacancy->id) }}"
                                                   class="text-xs text-amber-600 hover:text-amber-800 font-medium">
                                                    Просувати
                                                </a>
                                            @else
                                                <span class="text-xs text-amber-500 font-medium" title="В топі до {{ $vacancy->featured_until?->format('d.m.Y') }}">
                                                    В топі
                                                </span>
                                            @endif

                                            <button wire:click="delete({{ $vacancy->id }})"
                                                    wire:confirm="Ви впевнені, що хочете видалити цю вакансію?"
                                                    class="text-xs text-red-500 hover:text-red-700 font-medium">
                                                Видалити
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif
    </div>
</div>
