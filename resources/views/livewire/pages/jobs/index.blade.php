<?php

declare(strict_types=1);

use App\DTOs\VacancySearchDTO;
use App\Enums\EmploymentType;
use App\Enums\Language;
use App\Enums\Suitability;
use App\Models\Category;
use App\Services\VacancyService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $categoryId = '';

    #[Url(history: true)]
    public string $employmentType = '';

    #[Url(history: true)]
    public string $salaryMin = '';

    #[Url(history: true)]
    public string $salaryMax = '';

    /** @var array<string> */
    #[Url(history: true)]
    public array $languages = [];

    /** @var array<string> */
    #[Url(history: true)]
    public array $suitability = [];

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingCategoryId(): void     { $this->resetPage(); }
    public function updatingEmploymentType(): void { $this->resetPage(); }
    public function updatingSalaryMin(): void      { $this->resetPage(); }
    public function updatingSalaryMax(): void      { $this->resetPage(); }
    public function updatingLanguages(): void      { $this->resetPage(); }
    public function updatingSuitability(): void    { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryId', 'employmentType', 'salaryMin', 'salaryMax', 'languages', 'suitability']);
        $this->resetPage();
    }

    #[Computed]
    public function vacancies(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return app(VacancyService::class)->search(new VacancySearchDTO(
            search:         $this->search ?: null,
            categoryId:     $this->categoryId ? (int) $this->categoryId : null,
            employmentType: $this->employmentType ? EmploymentType::from($this->employmentType) : null,
            salaryMin:      $this->salaryMin ? (int) $this->salaryMin : null,
            salaryMax:      $this->salaryMax ? (int) $this->salaryMax : null,
            languages:      $this->languages,
            suitability:    $this->suitability,
        ));
    }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::orderBy('position')->orderBy('name')->get();
    }

    #[Computed]
    public function employmentTypes(): array { return EmploymentType::cases(); }

    #[Computed]
    public function languageOptions(): array { return Language::cases(); }

    #[Computed]
    public function suitabilityOptions(): array { return Suitability::cases(); }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->categoryId !== ''
            || $this->employmentType !== ''
            || $this->salaryMin !== ''
            || $this->salaryMax !== ''
            || !empty($this->languages)
            || !empty($this->suitability);
    }
}; ?>

<div x-data="{ filtersOpen: false }">

    {{-- Hero / Search --}}
    <div style="background: var(--color-bg-white); border-bottom: 1px solid var(--color-border); padding: var(--spacing-3xl) var(--spacing-lg);">
        <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
            <h1 style="font-size: 28px; font-weight: 800; color: var(--color-text-dark); margin-bottom: 4px;">
                Знайди свою роботу
            </h1>
            <p style="font-size: 14px; color: var(--color-text-gray); margin-bottom: var(--spacing-xl);">
                Тисячі вакансій по всій Україні
            </p>
            <div style="display: flex; gap: 8px; max-width: 700px; margin: 0 auto;">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Посада, компанія, ключове слово..."
                    style="flex: 1; height: 48px; padding: 0 var(--spacing-lg); font-size: 16px;
                           border: 1px solid #000000; border-radius: var(--radius-lg);
                           color: var(--color-text-dark); transition: all var(--transition-fast);
                           outline: none; background: var(--color-bg-white);"
                    onfocus="this.style.borderColor='#000000'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.1)'"
                    onblur="this.style.borderColor='#000000'; this.style.boxShadow='none'"
                />
                <button type="button"
                        style="height: 48px; padding: 0 32px; font-size: 16px; font-weight: 700;
                               background-color: #2d323b; color: #ffffff; border: none;
                               border-radius: var(--radius-lg); cursor: pointer; transition: background-color 0.2s; white-space: nowrap;"
                        onmouseover="this.style.backgroundColor='#3d434e'"
                        onmouseout="this.style.backgroundColor='#2d323b'">
                    ЗНАЙТИ
                </button>
            </div>
        </div>
    </div>

    {{-- Main layout --}}
    <div class="mj-main">

        {{-- Filters sidebar (desktop) --}}
        <aside class="mj-filters">
            @if($this->hasActiveFilters())
                <div class="filter-section">
                    <button wire:click="clearFilters"
                            style="font-size: 13px; font-weight: 700; color: var(--color-primary-blue);
                                   background: none; border: none; cursor: pointer; padding: 0;">
                        ← Скинути всі фільтри
                    </button>
                </div>
            @endif

            <div class="filter-section">
                <label for="cat-desktop" class="filter-label">Категорія</label>
                <select id="cat-desktop" wire:model.live="categoryId" class="filter-select">
                    <option value="">Всі категорії</option>
                    @foreach($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-section">
                <p class="filter-label">Тип зайнятості</p>
                <div class="radio-group">
                    @foreach($this->employmentTypes as $type)
                        <label class="radio-item">
                            <input type="radio" wire:model.live="employmentType" value="{{ $type->value }}"/>
                            <span>{{ $type->label() }}</span>
                        </label>
                    @endforeach
                    @if($employmentType)
                        <button wire:click="$set('employmentType', '')"
                                style="font-size: 12px; color: var(--color-primary-blue); background: none; border: none; cursor: pointer; text-align: left;">
                            Скинути
                        </button>
                    @endif
                </div>
            </div>

            <div class="filter-section">
                <p class="filter-label">Знання мов</p>
                <div class="radio-group">
                    @foreach($this->languageOptions as $lang)
                        <label class="radio-item">
                            <input type="checkbox" wire:model.live="languages" value="{{ $lang->value }}"
                                   style="width:16px; height:16px; accent-color: var(--color-primary-blue); cursor:pointer; flex-shrink:0;"/>
                            <span>{{ $lang->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="filter-section">
                <p class="filter-label">Підходить</p>
                <div class="radio-group">
                    @foreach($this->suitabilityOptions as $item)
                        <label class="radio-item">
                            <input type="checkbox" wire:model.live="suitability" value="{{ $item->value }}"
                                   style="width:16px; height:16px; accent-color: var(--color-primary-blue); cursor:pointer; flex-shrink:0;"/>
                            <span>{{ $item->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="filter-section">
                <p class="filter-label">Зарплата (UAH)</p>
                <div class="salary-row">
                    <div>
                        <label style="font-size: 11px; color: var(--color-text-gray); display: block; margin-bottom: 4px;">Від</label>
                        <input type="number" wire:model.live.debounce.600ms="salaryMin"
                               placeholder="0" min="0" class="salary-input"/>
                    </div>
                    <div>
                        <label style="font-size: 11px; color: var(--color-text-gray); display: block; margin-bottom: 4px;">До</label>
                        <input type="number" wire:model.live.debounce.600ms="salaryMax"
                               placeholder="Будь-яка" min="0" class="salary-input"/>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Jobs area --}}
        <div class="jobs-container">

            {{-- Jobs header --}}
            <div class="jobs-header">
                <span>
                    Знайдено <strong style="color: var(--color-text-dark);">{{ $this->vacancies->total() }}</strong> вакансій
                </span>
                {{-- Hamburger (mobile only) --}}
                <button class="mj-hamburger" @click="filtersOpen = true" aria-label="Відкрити фільтри">
                    ☰
                </button>
            </div>

            {{-- Vacancy cards --}}
            @forelse($this->vacancies as $vacancy)
                @php
                    $badgeClass = match($vacancy->employment_type->value) {
                        'full-time' => 'badge--full-time',
                        'part-time' => 'badge--part-time',
                        'contract'  => 'badge--contract',
                        'remote'    => 'badge--remote',
                        'hybrid'    => 'badge--hybrid',
                        default     => 'badge--category',
                    };
                @endphp

                <a href="{{ route('jobs.show', $vacancy->slug) }}" class="job-card {{ $vacancy->is_featured ? 'job-card--featured' : '' }}" style="text-decoration:none; display:block; color:inherit;">

                    <div class="job-info">
                        <div class="job-header-row">
                            {{-- Logo --}}
                            <div class="job-logo">
                                @if($vacancy->company->logo)
                                    <img src="{{ $vacancy->company->logo }}" alt="{{ $vacancy->company->name }}"/>
                                @else
                                    {{ strtoupper(substr($vacancy->company->name, 0, 1)) }}
                                @endif
                            </div>

                            <div class="job-title-wrap">
                                <h2 class="job-title">{{ $vacancy->title }}</h2>
                                <div class="job-details-row">
                                    <span class="job-company">{{ $vacancy->company->name }}</span>
                                    @if($vacancy->company->location)
                                        <span class="job-location">{{ $vacancy->company->location }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="job-badges">
                            <span class="badge {{ $badgeClass }}">
                                {{ $vacancy->employment_type->label() }}
                            </span>
                            <span class="badge badge--category">{{ $vacancy->category->name }}</span>
                            @if($vacancy->is_featured)
                                <span class="badge badge--featured">Топ</span>
                            @endif
                        </div>
                    </div>

                    <div class="job-meta">
                        @if($vacancy->salary_from)
                            <div class="job-salary">
                                {{ number_format($vacancy->salary_from, 0, '.', ' ') }}
                                @if($vacancy->salary_to)
                                    – {{ number_format($vacancy->salary_to, 0, '.', ' ') }}
                                @endif
                                {{ $vacancy->currency }}
                            </div>
                        @endif
                        @if($vacancy->published_at)
                            <div class="job-posted">{{ $vacancy->published_at->diffForHumans() }}</div>
                        @endif
                    </div>

                </a>
            @empty
                <div style="background: var(--color-bg-white); border: 1px solid var(--color-border);
                            border-radius: var(--radius-lg); padding: 64px var(--spacing-xl); text-align: center;">
                    <svg style="width: 48px; height: 48px; color: var(--color-text-light-gray); margin: 0 auto var(--spacing-lg);"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <p style="color: var(--color-text-gray); font-weight: 500; margin-bottom: 4px;">Вакансій не знайдено</p>
                    <p style="font-size: 13px; color: var(--color-text-light-gray);">Спробуйте змінити пошуковий запит або фільтри</p>
                </div>
            @endforelse

            {{-- Pagination --}}
            @if($this->vacancies->hasPages())
                <div class="mj-pagination">
                    {{ $this->vacancies->links() }}
                </div>
            @endif

        </div>
    </div>

    {{-- Mobile Filters Modal --}}
    <div class="filters-modal-overlay"
         x-show="filtersOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="filtersOpen = false"
         style="display: none;">

        <div class="filters-modal-content"
             x-show="filtersOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-y-full opacity-0"
             x-transition:enter-end="transform translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-y-0 opacity-100"
             x-transition:leave-end="transform translate-y-full opacity-0">

            <div class="filters-modal-header">
                <span class="filters-modal-title">Фільтри</span>
                <button class="filters-modal-close" @click="filtersOpen = false">✕</button>
            </div>

            @include('livewire.pages.jobs._filters')

            <button class="filters-modal-apply" @click="filtersOpen = false">
                Застосувати фільтри
            </button>

        </div>
    </div>

    <style>
        @keyframes spin {
          from { transform: rotate(0deg); }
          to   { transform: rotate(360deg); }
        }
    </style>

</div>
