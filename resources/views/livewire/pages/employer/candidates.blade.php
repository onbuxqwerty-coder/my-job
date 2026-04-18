<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Vacancy;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterVacancy = '';

    #[Url]
    public string $filterPeriod = 'all';

    #[Url]
    public string $filterRating = '';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    public int $perPage = 25;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterVacancy(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRating(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search        = '';
        $this->filterStatus  = '';
        $this->filterVacancy = '';
        $this->filterPeriod  = 'all';
        $this->filterRating  = '';
        $this->resetPage();
    }

    public function updateRating(int $applicationId, int $rating): void
    {
        Application::whereHas('vacancy', fn ($q) => $q->where('company_id', auth()->user()->company->id))
            ->findOrFail($applicationId)
            ->update(['rating' => $rating]);
    }

    #[Computed]
    public function companyId(): int
    {
        return auth()->user()->company->id;
    }

    #[Computed]
    public function vacancies(): \Illuminate\Database\Eloquent\Collection
    {
        return Vacancy::where('company_id', $this->companyId)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    #[Computed]
    public function statuses(): array
    {
        return ApplicationStatus::cases();
    }

    #[Computed]
    public function applications(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $companyId = $this->companyId;

        return Application::with(['user', 'vacancy'])
            ->whereHas('vacancy', fn ($q) => $q->where('company_id', $companyId))
            ->when($this->search !== '', function ($q) {
                $q->whereHas('user', fn ($u) => $u
                    ->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                );
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterVacancy !== '', fn ($q) => $q->where('vacancy_id', $this->filterVacancy))
            ->when($this->filterRating !== '', fn ($q) => $q->where('rating', $this->filterRating))
            ->when($this->filterPeriod !== 'all', function ($q) {
                $from = match ($this->filterPeriod) {
                    '7d'  => now()->subDays(7),
                    '30d' => now()->subDays(30),
                    '90d' => now()->subDays(90),
                    default => null,
                };
                if ($from) {
                    $q->where('created_at', '>=', $from);
                }
            })
            ->orderBy(
                in_array($this->sortBy, ['created_at', 'rating']) ? $this->sortBy : 'created_at',
                $this->sortDir === 'asc' ? 'asc' : 'desc'
            )
            ->paginate($this->perPage);
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->filterStatus !== ''
            || $this->filterVacancy !== ''
            || $this->filterPeriod !== 'all'
            || $this->filterRating !== '';
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
    <x-employer-tabs />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Sub-header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Мої кандидати</h2>
                <p class="text-sm text-gray-400 mt-0.5">{{ $this->applications->total() }} {{ trans_choice('кандидат|кандидати|кандидатів', $this->applications->total()) }}</p>
            </div>

            {{-- Search --}}
            <div class="w-72">
                <div class="relative">
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Пошук за ім'ям або email..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border employer-card-border p-4 mb-5 flex flex-wrap items-center gap-3">

            {{-- Status --}}
            <select wire:model.live="filterStatus"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Всі статуси</option>
                @foreach($this->statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>

            {{-- Vacancy --}}
            <select wire:model.live="filterVacancy"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Всі вакансії</option>
                @foreach($this->vacancies as $vacancy)
                    <option value="{{ $vacancy->id }}">{{ Str::limit($vacancy->title, 40) }}</option>
                @endforeach
            </select>

            {{-- Period --}}
            <select wire:model.live="filterPeriod"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all">Весь час</option>
                <option value="7d">Останні 7 днів</option>
                <option value="30d">Останній місяць</option>
                <option value="90d">Останні 3 місяці</option>
            </select>

            {{-- Rating --}}
            <select wire:model.live="filterRating"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Всі оцінки</option>
                @foreach([5, 4, 3, 2, 1] as $star)
                    <option value="{{ $star }}">{{ str_repeat('★', $star) }}</option>
                @endforeach
            </select>

            {{-- Per page --}}
            <select wire:model.live="perPage"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>

            @if($this->hasActiveFilters)
                <button wire:click="clearFilters"
                        class="text-sm text-red-500 hover:text-red-700 font-medium ml-auto">
                    Очистити фільтри
                </button>
            @endif
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border employer-card-border dark:border-gray-700 overflow-hidden">
            @if($this->applications->isEmpty())
                <div class="p-16 text-center text-gray-400 dark:text-gray-500">
                    {{ $this->hasActiveFilters ? 'Кандидатів за цими фільтрами не знайдено.' : 'Відгуків ще немає.' }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    <button wire:click="sort('users.name')" class="flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                        Кандидат
                                        @if($sortBy === 'users.name')
                                            <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Вакансія</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Статус</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    <button wire:click="sort('rating')" class="flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                        Оцінка
                                        @if($sortBy === 'rating')
                                            <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    <button wire:click="sort('created_at')" class="flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                        Дата
                                        @if($sortBy === 'created_at')
                                            <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Дії</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->applications as $application)
                                @php
                                    $statusColor = match($application->status->color()) {
                                        'gray'   => 'bg-gray-100 text-gray-600',
                                        'blue'   => 'bg-blue-100 text-blue-700',
                                        'yellow' => 'bg-yellow-100 text-yellow-700',
                                        'green'  => 'bg-green-100 text-green-700',
                                        'red'    => 'bg-red-100 text-red-600',
                                        default  => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <tr class="candidate-row cursor-pointer"
                                    wire:click="$navigate('{{ route('employer.candidate.detail', $application->id) }}')">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $application->user->name }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $application->user->email }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-gray-700 dark:text-gray-300">{{ Str::limit($application->vacancy->title, 35) }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                            {{ $application->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-0.5" wire:click.stop>
                                            @for($star = 1; $star <= 5; $star++)
                                                <button wire:click="updateRating({{ $application->id }}, {{ $star }})"
                                                        class="text-lg leading-none {{ $star <= ($application->rating ?? 0) ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600' }} hover:text-amber-400 transition-colors">
                                                    ★
                                                </button>
                                            @endfor
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-xs">
                                        {{ $application->created_at->format('d.m.Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-right" wire:click.stop>
                                        <a href="{{ route('employer.candidate.detail', $application->id) }}"
                                           class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            Деталі →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $this->applications->links() }}
                </div>
            @endif
        </div>
    </div>

    <style>
    .candidate-row {
        transition: background .2s, transform .2s;
    }
    .candidate-row:hover {
        background: #f0f7ff;
        transform: translateY(-2px);
    }
    [data-theme="dark"] .candidate-row:hover,
    .dark .candidate-row:hover {
        background: transparent;
        transform: translateY(-2px);
    }
    </style>
</div>
