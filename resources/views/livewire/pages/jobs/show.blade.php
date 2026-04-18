<?php

declare(strict_types=1);

use App\DTOs\ApplyDTO;
use App\Models\Vacancy;
use App\Services\ApplicationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public Vacancy $vacancy;
    public string $coverLetter = '';
    public $resume = null;
    public bool $submitted = false;
    public bool $alreadyApplied = false;
    public bool $showForm = false;

    public function mount(Vacancy $vacancy): void
    {
        $this->vacancy = $vacancy->load(['company', 'category', 'city', 'company.city']);

        if (auth()->check()) {
            $this->alreadyApplied = app(ApplicationService::class)
                ->alreadyApplied(auth()->user(), $vacancy);
        }
    }

    #[Computed]
    public function relatedVacancies(): \Illuminate\Database\Eloquent\Collection
    {
        return Vacancy::with(['company', 'category'])
            ->where('category_id', $this->vacancy->category_id)
            ->where('id', '!=', $this->vacancy->id)
            ->where('is_active', true)
            ->latest('published_at')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function applicationsCount(): int
    {
        return $this->vacancy->applications()->count();
    }

    public function toggleForm(): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }
        $this->showForm = !$this->showForm;
    }

    public function apply(): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $key = 'apply:' . auth()->id() . ':' . $this->vacancy->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('resume', 'Забагато спроб. Спробуйте пізніше.');
            return;
        }

        RateLimiter::hit($key, 300);

        $this->validate([
            'resume'      => 'required|file|mimes:pdf,doc,docx|max:5120',
            'coverLetter' => 'nullable|string|max:5000',
        ]);

        $ext  = $this->resume->getClientOriginalExtension();
        $path = $this->resume->storeAs('resumes', uniqid('cv_', true) . '.' . $ext, 'public');

        try {
            app(ApplicationService::class)->apply(
                auth()->user(),
                $this->vacancy,
                new ApplyDTO(
                    resumeUrl: Storage::url($path),
                    coverLetter: $this->coverLetter ?: null,
                )
            );

            $this->submitted  = true;
            $this->showForm   = false;
        } catch (\DomainException $e) {
            $this->addError('resume', $e->getMessage());
        }
    }
}; ?>

<div class="mj-show-bg seeker-dashboard-bg dark:bg-gray-900" style="min-height: 100vh;">
    <div class="mj-show-wrap">

        {{-- Breadcrumb --}}
        <nav class="mj-breadcrumb">
            <a href="{{ route('home') }}">Вакансії</a>
            <span class="mj-breadcrumb-sep">›</span>
            <a href="{{ route('home', ['categoryId' => $vacancy->category_id]) }}">{{ $vacancy->category->name }}</a>
            <span class="mj-breadcrumb-sep">›</span>
            <span>{{ $vacancy->title }}</span>
        </nav>

        <div class="mj-show-grid">

            {{-- ═══════════ MAIN CONTENT ═══════════ --}}
            <article class="mj-show-main">

                {{-- Job Header --}}
                <div class="mj-job-header">
                    <div class="mj-job-header-info">
                        <h1 class="mj-job-title">{{ $vacancy->title }}</h1>
                        <div class="mj-job-company-row">
                            <span class="mj-job-company-name">{{ $vacancy->company->name }}</span>
                            @if($vacancy->company->is_verified)
                                <span class="mj-verified-badge" title="Верифікована компанія">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Перевірена
                                </span>
                            @endif
                        </div>
                        @if($vacancy->published_at)
                            <div class="mj-job-date">{{ $vacancy->published_at->diffForHumans() }}</div>
                        @endif
                    </div>

                    <div class="mj-job-logo-wrap">
                        @if($vacancy->company->logo_url)
                            <img src="{{ $vacancy->company->logo_url }}"
                                 alt="{{ $vacancy->company->name }}"
                                 class="mj-job-logo-img"/>
                        @else
                            <div class="mj-job-logo-placeholder">
                                {{ strtoupper(substr($vacancy->company->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Salary --}}
                @if($vacancy->salary_from || $vacancy->salary_to)
                    <div class="mj-salary-block">
                        <svg class="mj-salary-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="mj-salary-text">
                            @if($vacancy->salary_from && $vacancy->salary_to)
                                {{ number_format($vacancy->salary_from, 0, '.', ' ') }} – {{ number_format($vacancy->salary_to, 0, '.', ' ') }} {{ $vacancy->currency }}
                            @elseif($vacancy->salary_from)
                                від {{ number_format($vacancy->salary_from, 0, '.', ' ') }} {{ $vacancy->currency }}
                            @else
                                до {{ number_format($vacancy->salary_to, 0, '.', ' ') }} {{ $vacancy->currency }}
                            @endif
                        </span>
                    </div>
                @endif

                {{-- Key Details --}}
                <div class="mj-job-details-row">
                    @php
                        $typeColors = [
                            'full-time' => 'mj-tag--blue',
                            'part-time' => 'mj-tag--purple',
                            'remote'    => 'mj-tag--green',
                            'hybrid'    => 'mj-tag--orange',
                            'contract'  => 'mj-tag--gray',
                        ];
                    @endphp
                    @foreach((array) $vacancy->employment_type as $et)
                    <span class="mj-tag {{ $typeColors[$et] ?? 'mj-tag--gray' }}">
                        <svg class="mj-tag-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ \App\Enums\EmploymentType::from($et)->label() }}
                    </span>
                    @endforeach

                    @if($vacancy->city)
                        <span class="mj-tag mj-tag--gray">
                            <svg class="mj-tag-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $vacancy->city->name }}
                        </span>
                    @elseif($vacancy->company->location)
                        <span class="mj-tag mj-tag--gray">
                            <svg class="mj-tag-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $vacancy->company->location }}
                        </span>
                    @endif

                    <span class="mj-tag mj-tag--category">
                        <svg class="mj-tag-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        {{ $vacancy->category->name }}
                    </span>

                    @if($vacancy->is_featured)
                        <span class="mj-tag mj-tag--featured">
                            ⭐ Топ вакансія
                        </span>
                    @endif
                </div>

                {{-- Suitability Tags --}}
                @if(!empty($vacancy->suitability) || !empty($vacancy->languages))
                    <div class="mj-suit-row">
                        @if(!empty($vacancy->suitability))
                            <span class="mj-suit-label">Підходить для:</span>
                            @foreach($vacancy->suitability as $suit)
                                @php $suitEnum = \App\Enums\Suitability::tryFrom($suit); @endphp
                                @if($suitEnum)
                                    <span class="mj-suit-tag">{{ $suitEnum->label() }}</span>
                                @endif
                            @endforeach
                        @endif
                        @if(!empty($vacancy->languages))
                            <span class="mj-suit-label">Мови:</span>
                            @foreach($vacancy->languages as $lang)
                                @php $langEnum = \App\Enums\Language::tryFrom($lang); @endphp
                                @if($langEnum)
                                    <span class="mj-lang-tag">{{ $langEnum->label() }}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                @endif

                <div class="mj-divider"></div>

                {{-- Description --}}
                <div class="mj-desc-section">
                    <h2 class="mj-section-title">Опис вакансії</h2>
                    <div class="mj-desc-body">
                        {!! nl2br(e($vacancy->description)) !!}
                    </div>
                </div>

                <div class="mj-divider"></div>

                {{-- Company Block --}}
                <div class="mj-company-block">
                    <div class="mj-company-block-logo">
                        @if($vacancy->company->logo_url)
                            <img src="{{ $vacancy->company->logo_url }}"
                                 alt="{{ $vacancy->company->name }}"
                                 class="mj-company-block-logo-img"/>
                        @else
                            <div class="mj-company-block-logo-placeholder">
                                {{ strtoupper(substr($vacancy->company->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                    <div class="mj-company-block-info">
                        <div class="mj-company-block-name-row">
                            <h3 class="mj-company-block-name">{{ $vacancy->company->name }}</h3>
                            @if($vacancy->company->is_verified)
                                <span class="mj-verified-badge">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Перевірена
                                </span>
                            @endif
                        </div>
                        @if($vacancy->company->location || $vacancy->company->city)
                            <div class="mj-company-block-location">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $vacancy->company->city?->name ?? $vacancy->company->location }}
                            </div>
                        @endif
                        @if($vacancy->company->description)
                            <p class="mj-company-block-desc">{{ Str::limit($vacancy->company->description, 200) }}</p>
                        @endif
                        <div class="mj-company-block-links">
                            <a href="{{ route('home', ['search' => $vacancy->company->name]) }}"
                               class="mj-company-block-link">
                                Всі вакансії компанії →
                            </a>
                            @if($vacancy->company->website)
                                <a href="{{ $vacancy->company->website }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="mj-company-block-link mj-company-block-link--ext">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    Вебсайт
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

            </article>

            {{-- ═══════════ SIDEBAR ═══════════ --}}
            <aside class="mj-show-sidebar">

                {{-- Apply CTA --}}
                <div class="mj-apply-card">

                    @if($submitted)
                        <div class="mj-apply-success">
                            <div class="mj-apply-success-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h3>Відгук надіслано!</h3>
                            <p>Роботодавець розгляне вашу заявку і зв'яжеться з вами.</p>
                        </div>

                    @elseif($alreadyApplied)
                        <div class="mj-apply-notice mj-apply-notice--success">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Ви вже відгукнулись на цю вакансію
                        </div>

                    @elseif(auth()->check() && auth()->user()->role === \App\Enums\UserRole::Employer)
                        <div class="mj-apply-notice">
                            Роботодавці не можуть подавати заявки.
                        </div>

                    @else
                        @if(!$showForm)
                            @if(!auth()->check())
                                <a href="{{ route('login') }}" class="mj-btn-apply">
                                    Увійти та відгукнутись
                                </a>
                            @else
                                <button wire:click="toggleForm" class="mj-btn-apply">
                                    Відгукнутись
                                </button>
                            @endif
                        @else
                            <div class="mj-apply-form-wrap">
                                <div class="mj-apply-form-header">
                                    <h3>Ваш відгук</h3>
                                    <button wire:click="toggleForm" class="mj-apply-form-close">✕</button>
                                </div>
                                <form wire:submit="apply" class="mj-apply-form">
                                    <div class="mj-form-field">
                                        <label class="mj-form-label">
                                            Резюме <span class="mj-required">*</span>
                                        </label>
                                        <input
                                            type="file"
                                            wire:model="resume"
                                            accept=".pdf,.doc,.docx"
                                            class="mj-file-input"
                                        />
                                        <p class="mj-form-hint">PDF, DOC, DOCX — макс. 5 МБ</p>
                                        @error('resume')
                                            <p class="mj-form-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="mj-form-field">
                                        <label class="mj-form-label">Супровідний лист</label>
                                        <textarea
                                            wire:model="coverLetter"
                                            rows="4"
                                            placeholder="Розкажіть чому ви підходите..."
                                            class="mj-textarea"
                                        ></textarea>
                                        @error('coverLetter')
                                            <p class="mj-form-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button
                                        type="submit"
                                        wire:loading.attr="disabled"
                                        class="mj-btn-apply"
                                    >
                                        <span wire:loading.remove wire:target="apply">Надіслати відгук</span>
                                        <span wire:loading wire:target="apply">Надсилання...</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endif

                    {{-- Telegram share --}}
                    <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($vacancy->title) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="mj-tg-share">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/>
                        </svg>
                        Поділитись у Telegram
                    </a>
                </div>

                {{-- Stats Card --}}
                <div class="mj-stats-card">
                    <h4 class="mj-stats-title">Статистика вакансії</h4>
                    <div class="mj-stats-row">
                        <div class="mj-stat-item">
                            <svg class="mj-stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                            </svg>
                            <div>
                                <div class="mj-stat-value">{{ $this->applicationsCount }}</div>
                                <div class="mj-stat-label">відгуків</div>
                            </div>
                        </div>
                        @if($vacancy->published_at)
                            <div class="mj-stat-item">
                                <svg class="mj-stat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <div>
                                    <div class="mj-stat-value">{{ $vacancy->published_at->format('d.m.Y') }}</div>
                                    <div class="mj-stat-label">опубліковано</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Related in sidebar --}}
                @if($this->relatedVacancies->isNotEmpty())
                    <div class="mj-sidebar-related">
                        <h4 class="mj-stats-title">Схожі вакансії</h4>
                        <div class="mj-sidebar-related-list">
                            @foreach($this->relatedVacancies->take(4) as $related)
                                <a href="{{ route('jobs.show', $related) }}" class="mj-sidebar-related-item">
                                    <div class="mj-sidebar-related-logo">
                                        @if($related->company->logo_url)
                                            <img src="{{ $related->company->logo_url }}" alt="{{ $related->company->name }}"/>
                                        @else
                                            {{ strtoupper(substr($related->company->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="mj-sidebar-related-info">
                                        <div class="mj-sidebar-related-title">{{ $related->title }}</div>
                                        <div class="mj-sidebar-related-company">{{ $related->company->name }}</div>
                                        @if($related->salary_from)
                                            <div class="mj-sidebar-related-salary">
                                                від {{ number_format($related->salary_from, 0, '.', ' ') }} {{ $related->currency }}
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

            </aside>
        </div>

        {{-- ═══════════ RELATED VACANCIES (bottom) ═══════════ --}}
        @if($this->relatedVacancies->isNotEmpty())
            <section class="mj-related-section">
                <div class="mj-related-header">
                    <h2 class="mj-related-title">Схожі вакансії</h2>
                    <a href="{{ route('home', ['categoryId' => $vacancy->category_id]) }}"
                       class="mj-related-all">
                        Всі вакансії категорії →
                    </a>
                </div>
                <div class="mj-related-grid">
                    @foreach($this->relatedVacancies as $related)
                        @php
                            $relBadgeColors = [
                                'full-time' => 'mj-tag--blue',
                                'part-time' => 'mj-tag--purple',
                                'remote'    => 'mj-tag--green',
                                'hybrid'    => 'mj-tag--orange',
                                'contract'  => 'mj-tag--gray',
                            ];
                        @endphp
                        <a href="{{ route('jobs.show', $related) }}" class="mj-related-card">
                            <div class="mj-related-card-top">
                                <div class="mj-related-card-logo">
                                    @if($related->company->logo_url)
                                        <img src="{{ $related->company->logo_url }}" alt="{{ $related->company->name }}"/>
                                    @else
                                        {{ strtoupper(substr($related->company->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div class="mj-related-card-info">
                                    <div class="mj-related-card-title">{{ $related->title }}</div>
                                    <div class="mj-related-card-company">{{ $related->company->name }}</div>
                                </div>
                            </div>
                            <div class="mj-related-card-footer">
                                @foreach((array) $related->employment_type as $ret)
                                <span class="mj-tag mj-tag--sm {{ $relBadgeColors[$ret] ?? 'mj-tag--gray' }}">{{ \App\Enums\EmploymentType::from($ret)->label() }}</span>
                                @endforeach
                                @if($related->salary_from)
                                    <span class="mj-related-card-salary">
                                        від {{ number_format($related->salary_from, 0, '.', ' ') }} {{ $related->currency }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

    </div>

<style>
/* ════════════════════════════════════════════
   JOB SHOW PAGE STYLES
════════════════════════════════════════════ */
.mj-show-bg {
    background-color: var(--color-bg-light);
    min-height: 100vh;
    padding: 24px 0 60px;
}

.mj-show-wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px;
}

/* Breadcrumb */
.mj-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 16px;
    color: var(--color-text-gray);
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.mj-breadcrumb a {
    color: var(--color-primary-blue);
    text-decoration: none;
}
.mj-breadcrumb a:hover { text-decoration: underline; }
.mj-breadcrumb span:last-child {
    color: var(--color-text-gray);
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.mj-breadcrumb-sep { color: var(--color-text-light-gray); }

/* Grid */
.mj-show-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1023px) {
    .mj-show-grid { grid-template-columns: 1fr; }
}

/* Main Article */
.mj-show-main {
    background: var(--color-bg-white);
    border: 1px solid #a7a7a7;
    border-radius: 12px;
    padding: 32px;
}
@media (max-width: 767px) {
    .mj-show-main { padding: 20px 16px; }
}

/* Job Header */
.mj-job-header {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 24px;
}
.mj-job-logo-wrap {
    width: 72px;
    height: 72px;
    flex-shrink: 0;
    border-radius: 12px;
    border: 1px solid var(--color-border);
    overflow: hidden;
}
.mj-job-logo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.mj-job-logo-placeholder {
    width: 100%;
    height: 100%;
    background: var(--color-bg-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    font-weight: 700;
    color: var(--color-text-gray);
    letter-spacing: -1px;
}
.mj-job-header-info { flex: 1; min-width: 0; }
.mj-job-title {
    font-size: 33px;
    font-weight: 800;
    color: var(--color-text-dark);
    line-height: 1.3;
    margin: 0 0 6px;
}
@media (max-width: 767px) {
    .mj-job-title { font-size: 27px; }
}
.mj-job-company-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}
.mj-job-company-name {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text-dark);
}
.mj-verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 13px;
    font-weight: 600;
    color: #059669;
    background: #d1fae5;
    padding: 2px 7px;
    border-radius: 20px;
}
.mj-job-date {
    font-size: 14px;
    color: var(--color-text-light-gray);
}

/* Salary */
.mj-salary-block {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
}
.mj-salary-icon {
    width: 20px;
    height: 20px;
    color: #16a34a;
    flex-shrink: 0;
}
.mj-salary-text {
    font-size: 24px;
    font-weight: 800;
    color: #16a34a;
    letter-spacing: -0.5px;
}

/* Detail Tags Row */
.mj-job-details-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}
.mj-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 16px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
}
.mj-tag-icon { width: 13px; height: 13px; flex-shrink: 0; }
.mj-tag--blue     { background: #dbeafe; color: #1d4ed8; }
.mj-tag--purple   { background: #ede9fe; color: #7c3aed; }
.mj-tag--green    { background: #dcfce7; color: #15803d; }
.mj-tag--orange   { background: #ffedd5; color: #c2410c; }
.mj-tag--gray     { background: #f3f4f6; color: #4b5563; }
.mj-tag--category { background: #e0f2fe; color: #0369a1; }
.mj-tag--featured { background: #fef9c3; color: #854d0e; }
.mj-tag--sm { font-size: 13px; padding: 3px 9px; }

/* Suitability */
.mj-suit-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding: 12px 16px;
    background: #fafafa;
    border-radius: 8px;
    border: 1px solid #a7a7a7;
}
.mj-suit-label {
    font-size: 14px;
    font-weight: 700;
    color: var(--color-text-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.mj-suit-tag {
    font-size: 14px;
    font-weight: 500;
    color: #6d28d9;
    background: #ede9fe;
    padding: 3px 10px;
    border-radius: 20px;
}
.mj-lang-tag {
    font-size: 14px;
    font-weight: 500;
    color: #0369a1;
    background: #e0f2fe;
    padding: 3px 10px;
    border-radius: 20px;
}

/* Divider */
.mj-divider {
    height: 1px;
    background: var(--color-border);
    margin: 28px 0;
}

/* Description */
.mj-section-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--color-text-dark);
    margin: 0 0 16px;
}
.mj-desc-body {
    font-size: 18px;
    line-height: 1.8;
    color: var(--color-text-dark);
}
.mj-desc-body br { display: block; content: ''; margin-top: 4px; }

/* Company Block */
.mj-company-block {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    border: 1px solid #a7a7a7;
    border-radius: 12px;
    padding: 16px;
}
.mj-company-block-logo {
    width: 56px;
    height: 56px;
    flex-shrink: 0;
    border-radius: 10px;
    border: 1px solid var(--color-border);
    overflow: hidden;
}
.mj-company-block-logo-img { width: 100%; height: 100%; object-fit: cover; }
.mj-company-block-logo-placeholder {
    width: 100%;
    height: 100%;
    background: var(--color-bg-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 700;
    color: var(--color-text-gray);
}
.mj-company-block-info { flex: 1; min-width: 0; }
.mj-company-block-name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.mj-company-block-name {
    font-size: 19px;
    font-weight: 700;
    color: var(--color-text-dark);
    margin: 0;
}
.mj-company-block-location {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 16px;
    color: var(--color-text-gray);
    margin-bottom: 8px;
}
.mj-company-block-desc {
    font-size: 16px;
    color: var(--color-text-gray);
    line-height: 1.6;
    margin: 0 0 10px;
}
.mj-company-block-links { display: flex; flex-wrap: wrap; gap: 12px; }
.mj-company-block-link {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-primary-blue);
    text-decoration: none;
}
.mj-company-block-link:hover { text-decoration: underline; }
.mj-company-block-link--ext {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: var(--color-text-gray);
}

/* ═══ SIDEBAR ═══ */
.mj-show-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
    position: sticky;
    top: 128px;
}

/* Apply Card */
.mj-apply-card {
    background: var(--color-bg-white);
    border: 1px solid #a7a7a7;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mj-btn-apply {
    display: block;
    width: 100%;
    text-align: center;
    background: #16a34a;
    color: #ffffff;
    font-size: 19px;
    font-weight: 700;
    padding: 14px 20px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    letter-spacing: 0.2px;
}
.mj-btn-apply:hover { background: #15803d; }
.mj-btn-apply:disabled { opacity: 0.6; cursor: not-allowed; }

.mj-apply-success {
    text-align: center;
    padding: 8px 0;
}
.mj-apply-success-icon {
    width: 48px;
    height: 48px;
    background: #dcfce7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
}
.mj-apply-success-icon svg { width: 24px; height: 24px; color: #16a34a; }
.mj-apply-success h3 { font-size: 18px; font-weight: 700; color: var(--color-text-dark); margin: 0 0 4px; }
.mj-apply-success p { font-size: 16px; color: var(--color-text-gray); margin: 0; }

.mj-apply-notice {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 16px;
    color: var(--color-text-gray);
    background: var(--color-bg-gray);
    padding: 10px 12px;
    border-radius: 8px;
}
.mj-apply-notice--success { color: #15803d; background: #dcfce7; }

.mj-apply-form-wrap {}
.mj-apply-form-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.mj-apply-form-header h3 { font-size: 18px; font-weight: 700; color: var(--color-text-dark); margin: 0; }
.mj-apply-form-close {
    background: none;
    border: none;
    font-size: 19px;
    color: var(--color-text-gray);
    cursor: pointer;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 4px;
}
.mj-apply-form-close:hover { background: var(--color-bg-gray); }

.mj-apply-form { display: flex; flex-direction: column; gap: 12px; }
.mj-form-field { display: flex; flex-direction: column; gap: 4px; }
.mj-form-label { font-size: 16px; font-weight: 600; color: var(--color-text-dark); }
.mj-required { color: #ef4444; }
.mj-form-hint { font-size: 13px; color: var(--color-text-light-gray); margin: 0; }
.mj-form-error { font-size: 14px; color: #ef4444; margin: 0; }
.mj-file-input {
    font-size: 16px;
    color: var(--color-text-gray);
}
.mj-file-input::file-selector-button {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-primary-blue);
    background: #dbeafe;
    border: none;
    border-radius: 6px;
    padding: 5px 10px;
    margin-right: 8px;
    cursor: pointer;
}
.mj-textarea {
    width: 100%;
    border: 1px solid var(--color-border-dark);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 16px;
    color: var(--color-text-dark);
    resize: none;
    font-family: inherit;
    line-height: 1.5;
    outline: none;
    transition: border-color 0.2s;
}
.mj-textarea:focus { border-color: var(--color-primary-blue); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

.mj-tg-share {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 16px;
    font-weight: 600;
    color: #0088cc;
    text-decoration: none;
    padding: 8px;
    border-radius: 8px;
    transition: background 0.2s;
}
.mj-tg-share:hover { background: #e8f4fb; }

/* Stats Card */
.mj-stats-card {
    background: var(--color-bg-white);
    border: 1px solid #a7a7a7;
    border-radius: 12px;
    padding: 20px;
}
.mj-stats-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--color-text-gray);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin: 0 0 14px;
}
.mj-stats-row {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.mj-stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
}
.mj-stat-icon { width: 18px; height: 18px; color: var(--color-text-light-gray); flex-shrink: 0; }
.mj-stat-value { font-size: 19px; font-weight: 700; color: var(--color-text-dark); line-height: 1; }
.mj-stat-label { font-size: 14px; color: var(--color-text-gray); margin-top: 2px; }

/* Sidebar Related */
.mj-sidebar-related {
    background: var(--color-bg-white);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 20px;
}
.mj-sidebar-related-list { display: flex; flex-direction: column; gap: 0; }
.mj-sidebar-related-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid var(--color-border);
    text-decoration: none;
    transition: background 0.15s;
    border-radius: 6px;
}
.mj-sidebar-related-item:last-child { border-bottom: none; padding-bottom: 0; }
.mj-sidebar-related-item:first-child { padding-top: 0; }
.mj-sidebar-related-item:hover { background: var(--color-bg-light); }
.mj-sidebar-related-logo {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: 6px;
    border: 1px solid var(--color-border);
    overflow: hidden;
    background: var(--color-bg-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    color: var(--color-text-gray);
}
.mj-sidebar-related-logo img { width: 100%; height: 100%; object-fit: cover; }
.mj-sidebar-related-info { flex: 1; min-width: 0; }
.mj-sidebar-related-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-text-dark);
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.mj-sidebar-related-item:hover .mj-sidebar-related-title { color: var(--color-primary-blue); }
.mj-sidebar-related-company { font-size: 18px; color: var(--color-text-gray); margin-top: 1px; }
.mj-sidebar-related-salary { font-size: 18px; color: #16a34a; font-weight: 600; margin-top: 2px; }

/* ═══ RELATED BOTTOM SECTION ═══ */
.mj-related-section {
    margin-top: 40px;
}
.mj-related-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.mj-related-title {
    font-size: 24px;
    font-weight: 800;
    color: var(--color-text-dark);
    margin: 0;
}
.mj-related-all {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-primary-blue);
    text-decoration: none;
}
.mj-related-all:hover { text-decoration: underline; }

.mj-related-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
@media (max-width: 1023px) {
    .mj-related-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 639px) {
    .mj-related-grid { grid-template-columns: 1fr; }
}

.mj-related-card {
    background: var(--color-bg-white);
    border: 1px solid #a7a7a7;
    border-radius: 10px;
    padding: 16px;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.mj-related-card:hover {
    border-color: var(--color-primary-blue);
    box-shadow: var(--shadow-md);
}
.mj-related-card-top { display: flex; gap: 10px; align-items: flex-start; }
.mj-related-card-logo {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    overflow: hidden;
    background: var(--color-bg-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    color: var(--color-text-gray);
}
.mj-related-card-logo img { width: 100%; height: 100%; object-fit: cover; }
.mj-related-card-info { flex: 1; min-width: 0; }
.mj-related-card-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--color-text-dark);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.mj-related-card:hover .mj-related-card-title { color: var(--color-primary-blue); }
.mj-related-card-company {
    font-size: 18px;
    color: var(--color-text-gray);
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.mj-related-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px;
}
.mj-related-card-salary {
    font-size: 18px;
    font-weight: 700;
    color: #16a34a;
}
</style>
</div>
