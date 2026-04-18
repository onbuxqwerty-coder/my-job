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

    /** @var array<string> */
    #[Url(history: true)]
    public array $employmentType = [];

    #[Url(history: true)]
    public string $cityId = '';

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

    #[Url(history: true)]
    public int $perPage = 10;

    /** @var array<int> */
    public array $perPageOptions = [10, 25, 50];

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingCityId(): void         { $this->resetPage(); }
    public function updatingCategoryId(): void     { $this->resetPage(); }
    public function updatingEmploymentType(): void { $this->resetPage(); }
    public function updatingSalaryMin(): void      { $this->resetPage(); }
    public function updatingSalaryMax(): void      { $this->resetPage(); }
    public function updatingLanguages(): void      { $this->resetPage(); }
    public function updatingSuitability(): void    { $this->resetPage(); }
    public function updatingPerPage(): void        { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'cityId', 'categoryId', 'employmentType', 'salaryMin', 'salaryMax', 'languages', 'suitability', 'perPage']);
        $this->resetPage();
    }

    #[Computed]
    public function vacancies(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return app(VacancyService::class)->search(new VacancySearchDTO(
            search:         $this->search ?: null,
            categoryId:     $this->categoryId ? (int) $this->categoryId : null,
            employmentTypes: $this->employmentType,
            salaryMin:      $this->salaryMin ? (int) $this->salaryMin : null,
            salaryMax:      $this->salaryMax ? (int) $this->salaryMax : null,
            languages:      $this->languages,
            suitability:    $this->suitability,
            cityId:         $this->cityId ? (int) $this->cityId : null,
            perPage:        in_array($this->perPage, $this->perPageOptions) ? $this->perPage : 10,
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
            || $this->cityId !== ''
            || $this->categoryId !== ''
            || !empty($this->employmentType)
            || $this->salaryMin !== ''
            || $this->salaryMax !== ''
            || !empty($this->languages)
            || !empty($this->suitability);
    }
}; ?>

<div x-data="{ filtersOpen: false }" class="seeker-dashboard-bg dark:bg-gray-900">

    {{-- Hero / Search --}}
    <div class="index-hero-bg" style="border-bottom: 1px solid var(--color-border); padding: var(--spacing-3xl) var(--spacing-lg);">
        <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
            <h1 class="hero-title">
                Новий сервіс пошуку роботи в Україні
            </h1>
            <p style="font-size: 14px; color: var(--color-text-gray); margin-bottom: var(--spacing-xl);">
                Тисячі вакансій по всій Україні
            </p>
            <div class="hero-search-row">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Посада, компанія, ключове слово..."
                    class="hero-search-input"
                    onfocus="this.style.borderColor='#000000'; this.style.boxShadow='0 0 0 3px rgba(0,0,0,0.1)'"
                    onblur="this.style.borderColor='#000000'; this.style.boxShadow='none'"
                />
                <div class="hero-city-wrap">
                    <livewire:city-search wire:model.live="cityId" :key="'city-search'" />
                </div>
                <button type="button" class="hero-search-btn"
                        onmouseover="this.style.backgroundColor='#3d434e'"
                        onmouseout="this.style.backgroundColor='#2d323b'">
                    ЗНАЙТИ
                </button>
            </div>
        </div>
    </div>

    <style>
        .hero-title {
            font-size: 28px; font-weight: 800;
            color: var(--color-text-dark); margin-bottom: 4px;
        }
        .hero-search-row {
            display: flex;
            gap: 8px;
            max-width: 860px;
            margin: 0 auto;
            align-items: flex-start;
        }
        .hero-search-input {
            flex: 1;
            height: 48px;
            line-height: 48px;
            padding: 0 var(--spacing-lg);
            font-size: 16px;
            border: 1px solid #000000;
            border-radius: var(--radius-lg);
            color: var(--color-text-dark);
            transition: all var(--transition-fast);
            outline: none;
            background: #ffffff;
            min-width: 0;
            box-sizing: border-box;
        }
        .hero-city-wrap {
            min-width: 220px;
        }
        .hero-search-btn {
            height: 48px;
            padding: 0 32px;
            font-size: 16px;
            font-weight: 700;
            background-color: #2d323b;
            color: #ffffff;
            border: none;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: background-color 0.2s;
            white-space: nowrap;
        }
        @media (max-width: 767px) {
            .hero-title { font-size: 22px; }
            .hero-search-row {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .hero-search-input,
            .hero-city-wrap,
            .hero-search-btn {
                width: 100%;
                min-width: unset;
                height: 48px;
                box-sizing: border-box;
            }
            .hero-city-wrap {
                overflow: visible;
                position: relative;
            }
            .hero-city-wrap > div {
                height: 48px;
                overflow: visible;
            }
            .hero-city-wrap .city-dropdown {
                max-height: 280px; /* ~7 рядків по 40px */
                min-height: 280px;
            }
            .hero-search-btn {
                padding: 0;
            }
        }
    </style>

    {{-- Main layout --}}
    <div class="mj-main">

        {{-- Filters sidebar (desktop) --}}
        <aside class="mj-filters" style="background: #d2d2d2; border: 1px solid #a7a7a7;">
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
                @php
                    $etLabels = collect($this->employmentTypes)->mapWithKeys(fn($t) => [$t->value => $t->label()])->all();
                @endphp
                <div x-data="{
                    open: false,
                    selected: $wire.entangle('employmentType'),
                    labels: {{ json_encode($etLabels) }},
                    get display() {
                        return this.selected.length
                            ? this.selected.map(v => this.labels[v]).join('; ')
                            : 'Оберіть...';
                    }
                }" @click.outside="open = false" style="position:relative;">
                    <button type="button" @click="open = !open"
                            style="width:100%; border:1px solid rgba(255,255,255,.15); border-radius:10px; padding:8px 12px; font-size:13px; background:rgba(255,255,255,.07); display:flex; justify-content:space-between; align-items:center; cursor:pointer; color:#e2e8f0; text-align:left;">
                        <span x-text="display" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:90%;"></span>
                        <svg style="width:14px; height:14px; flex-shrink:0; opacity:.6;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition
                         style="position:absolute; z-index:50; top:calc(100% + 4px); left:0; right:0; background:#1e293b; border:1px solid rgba(255,255,255,.12); border-radius:10px; box-shadow:0 4px 16px rgba(0,0,0,.3); overflow:hidden;">
                        @foreach($this->employmentTypes as $type)
                            <label style="display:flex; align-items:center; gap:10px; padding:8px 12px; cursor:pointer; font-size:13px; color:#e2e8f0;"
                                   onmouseover="this.style.background='rgba(255,255,255,.08)'" onmouseout="this.style.background=''">
                                <input type="checkbox" x-model="selected" value="{{ $type->value }}"
                                       wire:ignore
                                       style="width:15px; height:15px; accent-color:#3b82f6; cursor:pointer; flex-shrink:0;"/>
                                {{ $type->label() }}
                            </label>
                        @endforeach
                        <div x-show="selected.length" style="padding:6px 12px; border-top:1px solid rgba(255,255,255,.08);">
                            <button type="button" @click="selected = []"
                                    style="font-size:12px; color:#60a5fa; background:none; border:none; cursor:pointer; padding:0;">
                                Скинути
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filter-section">
                <p class="filter-label">Знання мов</p>
                @php
                    $langLabels = collect($this->languageOptions)->mapWithKeys(fn($l) => [$l->value => $l->label()])->all();
                @endphp
                <div x-data="{
                    open: false,
                    selected: $wire.entangle('languages'),
                    labels: {{ json_encode($langLabels) }},
                    get display() {
                        return this.selected.length
                            ? this.selected.map(v => this.labels[v]).join('; ')
                            : 'Оберіть...';
                    }
                }" @click.outside="open = false" style="position:relative;">
                    <button type="button" @click="open = !open"
                            style="width:100%; border:1px solid rgba(255,255,255,.15); border-radius:10px; padding:8px 12px; font-size:13px; background:rgba(255,255,255,.07); display:flex; justify-content:space-between; align-items:center; cursor:pointer; color:#e2e8f0; text-align:left;">
                        <span x-text="display" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:90%;"></span>
                        <svg style="width:14px; height:14px; flex-shrink:0; opacity:.6;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition
                         style="position:absolute; z-index:50; top:calc(100% + 4px); left:0; right:0; background:#1e293b; border:1px solid rgba(255,255,255,.12); border-radius:10px; box-shadow:0 4px 16px rgba(0,0,0,.3); overflow:hidden;">
                        @foreach($this->languageOptions as $lang)
                            <label style="display:flex; align-items:center; gap:10px; padding:8px 12px; cursor:pointer; font-size:13px; color:#e2e8f0;"
                                   onmouseover="this.style.background='rgba(255,255,255,.08)'" onmouseout="this.style.background=''">
                                <input type="checkbox" x-model="selected" value="{{ $lang->value }}"
                                       wire:ignore
                                       style="width:15px; height:15px; accent-color:#3b82f6; cursor:pointer; flex-shrink:0;"/>
                                {{ $lang->label() }}
                            </label>
                        @endforeach
                        <div x-show="selected.length" style="padding:6px 12px; border-top:1px solid rgba(255,255,255,.08);">
                            <button type="button" @click="selected = []"
                                    style="font-size:12px; color:#60a5fa; background:none; border:none; cursor:pointer; padding:0;">
                                Скинути
                            </button>
                        </div>
                    </div>
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
                    $badgeClassMap = [
                        'full-time' => 'badge--full-time',
                        'part-time' => 'badge--part-time',
                        'contract'  => 'badge--contract',
                        'remote'    => 'badge--remote',
                        'hybrid'    => 'badge--hybrid',
                    ];
                @endphp

                <a href="{{ route('jobs.show', $vacancy) }}" wire:navigate class="job-card {{ $vacancy->is_featured && $vacancy->is_top ? 'job-card--hot-top' : ($vacancy->is_featured ? 'job-card--featured' : '') }}">

                    <div class="job-info">
                        <div class="job-header-row">
                            <div class="job-title-wrap">
                                @if($vacancy->is_featured)
                                    <div class="job-hot-label">🔥 Гаряча вакансія</div>
                                @endif
                                <h2 class="job-title">{{ $vacancy->title }}</h2>
                                @if($vacancy->salary_from)
                                    <div class="job-salary">
                                        {{ number_format($vacancy->salary_from, 0, '.', ' ') }}
                                        @if($vacancy->salary_to)
                                            – {{ number_format($vacancy->salary_to, 0, '.', ' ') }}
                                        @endif
                                        {{ $vacancy->currency }}
                                    </div>
                                @endif
                                <div class="job-details-row">
                                    <span class="job-company">{{ $vacancy->company->name }}</span>
                                    @if($vacancy->city)
                                        <span class="job-location">{{ $vacancy->city->name }}</span>
                                    @elseif($vacancy->company->location)
                                        <span class="job-location">{{ $vacancy->company->location }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Logo --}}
                            <div class="job-logo">
                                @if($vacancy->company->logo_url)
                                    <img src="{{ $vacancy->company->logo_url }}" alt="{{ $vacancy->company->name }}"/>
                                @else
                                    {{ strtoupper(substr($vacancy->company->name, 0, 1)) }}
                                @endif
                            </div>
                        </div>

                        <div class="job-badges">
                            @foreach((array) $vacancy->employment_type as $et)
                                <span class="badge {{ $badgeClassMap[$et] ?? 'badge--category' }}">
                                    {{ \App\Enums\EmploymentType::from($et)->label() }}
                                </span>
                            @endforeach
                            <span class="badge badge--category">{{ $vacancy->category->name }}</span>
                            @if($vacancy->is_top)
                                <span class="badge badge--featured">⭐ Топ</span>
                            @endif
                        </div>
                    </div>

                </a>
            @empty
                <div class="index-hero-bg" style="border: 1px solid var(--color-border);
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

            {{-- Pagination + per-page --}}
            @if($this->vacancies->hasPages() || $this->vacancies->total() > min($perPageOptions))
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
                    <div class="mj-pagination" style="margin-top: 0;">
                        {{ $this->vacancies->links() }}
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                        <label for="per-page-select" style="font-size: 13px; color: var(--color-text-gray); white-space: nowrap;">
                            Показувати:
                        </label>
                        <select id="per-page-select"
                                wire:model.live="perPage"
                                style="height: 34px; padding: 0 8px; font-size: 13px; border: 1px solid #a7a7a7;
                                       border-radius: var(--radius-md); background: var(--color-bg-card);
                                       color: var(--color-text-dark); cursor: pointer;">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
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
