<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $sortBy = 'newest';

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedSortBy(): void    { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->sortBy = 'newest';
        $this->resetPage();
    }

    #[Computed]
    public function applications(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = auth()->user()->applications()
            ->with(['vacancy.company', 'vacancy.city', 'interviews'])
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn($q) => $q->whereHas('vacancy', fn($vq) =>
                $vq->where('title', 'like', "%{$this->search}%")
                   ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', "%{$this->search}%"))
            ));

        match ($this->sortBy) {
            'oldest' => $query->oldest(),
            'status' => $query->orderBy('status'),
            default  => $query->latest(),
        };

        return $query->paginate(15);
    }

    #[Computed]
    public function counts(): array
    {
        $base = auth()->user()->applications();
        $result = ['all' => $base->count()];

        foreach (ApplicationStatus::cases() as $status) {
            $result[$status->value] = (clone $base)->where('status', $status->value)->count();
        }

        return $result;
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
    <x-seeker-tabs />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Status tabs --}}
        <div class="flex flex-wrap gap-2">
            <button wire:click="$set('filterStatus', '')"
                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                           {{ $filterStatus === '' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-300' }}">
                Всі ({{ $this->counts['all'] }})
            </button>
            @foreach(App\Enums\ApplicationStatus::cases() as $status)
                <button wire:click="$set('filterStatus', '{{ $status->value }}')"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                               {{ $filterStatus === $status->value ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-300' }}">
                    {{ $status->label() }} ({{ $this->counts[$status->value] ?? 0 }})
                </button>
            @endforeach
        </div>

        {{-- Search & sort --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Пошук по вакансії або компанії..."
                       class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            </div>
            <select wire:model.live="sortBy"
                    class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="newest">Спочатку нові</option>
                <option value="oldest">Спочатку старі</option>
                <option value="status">За статусом</option>
            </select>
            @if($search || $filterStatus)
                <button wire:click="clearFilters"
                        class="px-4 py-2.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    Скинути
                </button>
            @endif
        </div>

        {{-- List --}}
        @php
            $statusColors = [
                'pending'   => 'bg-gray-100 text-gray-700',
                'screening' => 'bg-blue-100 text-blue-700',
                'interview' => 'bg-yellow-100 text-yellow-700',
                'hired'     => 'bg-green-100 text-green-700',
                'rejected'  => 'bg-red-100 text-red-700',
            ];
        @endphp

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            @forelse($this->applications as $app)
                <a href="{{ route('seeker.application.detail', $app->id) }}"
                   class="flex items-center gap-4 px-6 py-4 border-b border-gray-50 last:border-b-0 hover:bg-gray-50 transition-colors group">

                    {{-- Company logo --}}
                    <div class="w-11 h-11 rounded-xl bg-blue-50 border border-gray-100 flex items-center justify-center font-bold text-blue-600 shrink-0 overflow-hidden">
                        @if($app->vacancy->company->logo_url)
                            <img src="{{ $app->vacancy->company->logo_url }}"
                                 alt="{{ $app->vacancy->company->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            {{ mb_strtoupper(mb_substr($app->vacancy->company->name, 0, 1)) }}
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors truncate">{{ $app->vacancy->title }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $app->vacancy->company->name }}
                            @if($app->vacancy->city) · {{ $app->vacancy->city->name }} @endif
                        </p>
                    </div>

                    {{-- Interview badge --}}
                    @if($app->interviews->where('status', \App\Enums\InterviewStatus::Scheduled)->count())
                        <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                            Є співбесіда
                        </span>
                    @endif

                    {{-- Status --}}
                    <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColors[$app->status->value] ?? '' }}">
                        {{ $app->status->label() }}
                    </span>

                    {{-- Date --}}
                    <span class="shrink-0 text-xs text-gray-400">{{ $app->created_at->format('d.m.Y') }}</span>

                    {{-- Arrow --}}
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @empty
                <div class="py-16 text-center text-gray-400">
                    <p class="text-sm">Заявок не знайдено.</p>
                    <a href="{{ route('home') }}" class="mt-2 inline-block text-sm text-blue-600 hover:underline">Переглянути вакансії →</a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div>{{ $this->applications->links() }}</div>

    </div>
</div>
