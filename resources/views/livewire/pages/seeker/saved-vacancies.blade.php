<?php

declare(strict_types=1);

use App\Models\SavedVacancy;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public function unsave(int $vacancyId): void
    {
        auth()->user()->savedVacancies()->detach($vacancyId);
    }

    #[Computed]
    public function vacancies(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return auth()->user()
            ->savedVacancies()
            ->with(['company', 'city', 'category'])
            ->where('vacancies.is_active', true)
            ->paginate(12);
    }

    #[Computed]
    public function totalSaved(): int
    {
        return auth()->user()->savedVacancies()->count();
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg">
    <x-seeker-tabs />

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Збережені вакансії</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $this->totalSaved }} {{ $this->totalSaved === 1 ? 'вакансія' : ($this->totalSaved < 5 ? 'вакансії' : 'вакансій') }} збережено</p>
            </div>
        </div>

        @if($this->vacancies->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-16 text-center">
                <div class="w-14 h-14 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                </div>
                <p class="text-gray-700 font-semibold">Немає збережених вакансій</p>
                <p class="text-sm text-gray-400 mt-1">Натискайте "Зберегти" на сторінках вакансій, щоб повернутись до них пізніше</p>
                <a href="{{ route('home') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">
                    Переглянути вакансії →
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($this->vacancies as $vacancy)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                        <a href="{{ route('jobs.show', $vacancy->slug) }}" class="block p-5 flex-1 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl border border-gray-100 overflow-hidden bg-gray-50 flex items-center justify-center text-base font-bold text-gray-400 flex-shrink-0">
                                    @if($vacancy->company->logo_url)
                                        <img src="{{ $vacancy->company->logo_url }}" alt="{{ $vacancy->company->name }}" class="w-full h-full object-cover"/>
                                    @else
                                        {{ strtoupper(substr($vacancy->company->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-gray-900 leading-tight line-clamp-2">{{ $vacancy->title }}</p>
                                    <p class="text-sm text-gray-500 mt-0.5">{{ $vacancy->company->name }}</p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if($vacancy->city)
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                        {{ $vacancy->city->name }}
                                    </span>
                                @endif
                                @if($vacancy->salary_from || $vacancy->salary_to)
                                    <span class="text-xs font-semibold text-emerald-600">
                                        {{ $vacancy->salary_from ? number_format($vacancy->salary_from, 0, '.', ' ') : '' }}
                                        @if($vacancy->salary_from && $vacancy->salary_to) – @endif
                                        {{ $vacancy->salary_to ? number_format($vacancy->salary_to, 0, '.', ' ') : '' }}
                                        {{ $vacancy->currency }}
                                    </span>
                                @endif
                            </div>
                        </a>

                        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ $vacancy->category?->name }}</span>
                            <button wire:click="unsave({{ $vacancy->id }})"
                                    wire:confirm="Видалити з збережених?"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-700 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Видалити
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $this->vacancies->links() }}
            </div>
        @endif
    </div>
</div>
