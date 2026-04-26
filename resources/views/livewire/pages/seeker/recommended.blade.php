<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Vacancy;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public int $categoryId = 0;

    public function updatedCategoryId(): void { $this->resetPage(); }

    #[Computed]
    public function vacancies(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $appliedIds = auth()->user()->applications()->pluck('vacancy_id');
        $savedIds   = auth()->user()->savedVacancies()->pluck('vacancies.id');

        return Vacancy::with(['company', 'city', 'category'])
            ->where('is_active', true)
            ->whereNotIn('id', $appliedIds)
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->latest('published_at')
            ->paginate(12);
    }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::orderBy('position')->get();
    }

    #[Computed]
    public function savedIds(): array
    {
        return auth()->user()->savedVacancies()->pluck('vacancies.id')->toArray();
    }

    public function toggleSave(int $vacancyId): void
    {
        auth()->user()->savedVacancies()->toggle($vacancyId);
        unset($this->savedIds);
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg">
    <x-seeker-tabs />

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900">Рекомендовані вакансії</h2>
            <p class="text-sm text-gray-500 mt-0.5">Актуальні вакансії, на які ви ще не відгукувались</p>
        </div>

        {{-- Category filter --}}
        <div class="mb-5 flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
            <button wire:click="$set('categoryId', 0)"
                    class="shrink-0 px-3 py-1.5 rounded-full text-sm font-medium transition
                           {{ $categoryId === 0 ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-blue-300' }}">
                Всі категорії
            </button>
            @foreach($this->categories as $cat)
                <button wire:click="$set('categoryId', {{ $cat->id }})"
                        class="shrink-0 px-3 py-1.5 rounded-full text-sm font-medium transition
                               {{ $categoryId === $cat->id ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-blue-300' }}">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>

        @if($this->vacancies->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-16 text-center">
                <p class="text-gray-500">Нових вакансій не знайдено</p>
                @if($categoryId)
                    <button wire:click="$set('categoryId', 0)" class="mt-3 text-sm text-blue-600 hover:underline">
                        Показати всі категорії
                    </button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($this->vacancies as $vacancy)
                    @php $isSaved = in_array($vacancy->id, $this->savedIds, true); @endphp
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
                                @if($vacancy->is_featured)
                                    <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">⭐ Топ</span>
                                @endif
                            </div>
                        </a>

                        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ $vacancy->category?->name }}</span>
                            <button wire:click="toggleSave({{ $vacancy->id }})"
                                    class="inline-flex items-center gap-1 text-xs font-medium transition
                                           {{ $isSaved ? 'text-blue-600 hover:text-blue-800' : 'text-gray-400 hover:text-blue-600' }}">
                                <svg class="w-3.5 h-3.5" fill="{{ $isSaved ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                {{ $isSaved ? 'Збережено' : 'Зберегти' }}
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
