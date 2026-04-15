<?php

declare(strict_types=1);

use App\Enums\InterviewStatus;
use App\Models\Interview;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $tab = 'upcoming';

    #[Computed]
    public function upcoming(): \Illuminate\Database\Eloquent\Collection
    {
        return Interview::with(['application.vacancy.company', 'application.vacancy.city'])
            ->whereHas('application', fn($q) => $q->where('user_id', auth()->id()))
            ->where('scheduled_at', '>=', now())
            ->where('status', InterviewStatus::Scheduled)
            ->orderBy('scheduled_at')
            ->get();
    }

    #[Computed]
    public function past(): \Illuminate\Database\Eloquent\Collection
    {
        return Interview::with(['application.vacancy.company', 'application.vacancy.city'])
            ->whereHas('application', fn($q) => $q->where('user_id', auth()->id()))
            ->where(fn($q) =>
                $q->where('scheduled_at', '<', now())
                  ->orWhere('status', InterviewStatus::Cancelled)
            )
            ->latest('scheduled_at')
            ->get();
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
    <x-seeker-tabs />

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Tabs --}}
        <div class="flex gap-2">
            <button wire:click="$set('tab', 'upcoming')"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors
                           {{ $tab === 'upcoming' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-300' }}">
                Заплановані ({{ $this->upcoming->count() }})
            </button>
            <button wire:click="$set('tab', 'past')"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors
                           {{ $tab === 'past' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-300' }}">
                Минулі ({{ $this->past->count() }})
            </button>
        </div>

        @php
            $interviews = $tab === 'upcoming' ? $this->upcoming : $this->past;
            $typeIcons  = ['video' => '📹', 'phone' => '📞', 'in_person' => '🏢', 'other' => '📅'];
        @endphp

        <div class="space-y-4">
            @forelse($interviews as $iv)
                @php
                    $vacancy = $iv->application->vacancy;
                    $company = $vacancy->company;
                    $isPast  = $iv->scheduled_at < now() || $iv->status === InterviewStatus::Cancelled;
                @endphp
                <div class="bg-white rounded-2xl border {{ $isPast ? 'border-gray-100 opacity-75' : 'border-purple-200' }} shadow-sm p-5">
                    <div class="flex items-start gap-4">

                        {{-- Date block --}}
                        <div class="shrink-0 w-14 text-center bg-{{ $isPast ? 'gray' : 'purple' }}-50 rounded-xl p-2">
                            <p class="text-xs font-semibold text-{{ $isPast ? 'gray' : 'purple' }}-500 uppercase">{{ $iv->scheduled_at->format('M') }}</p>
                            <p class="text-2xl font-bold text-{{ $isPast ? 'gray' : 'purple' }}-800 leading-none">{{ $iv->scheduled_at->format('d') }}</p>
                            <p class="text-xs text-{{ $isPast ? 'gray' : 'purple' }}-500">{{ $iv->scheduled_at->format('H:i') }}</p>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900">{{ $vacancy->title }}</p>
                            <p class="text-sm text-gray-500">{{ $company->name }}
                                @if($vacancy->city) · {{ $vacancy->city->name }} @endif
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $typeIcons[$iv->type->value] ?? '📅' }} {{ $iv->type->label() }}
                                @if($iv->duration_minutes) · {{ $iv->duration_minutes }} хв @endif
                            </p>
                            @if($iv->notes)
                                <p class="text-sm text-gray-500 mt-1.5 italic">{{ $iv->notes }}</p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="shrink-0 flex flex-col gap-2 items-end">
                            @if(!$isPast && $iv->status === InterviewStatus::Scheduled)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                    {{ $iv->scheduled_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                                    {{ $iv->status->label() }}
                                </span>
                            @endif

                            @if($iv->meeting_link && !$isPast)
                                <a href="{{ $iv->meeting_link }}" target="_blank"
                                   class="px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-xl hover:bg-purple-700 transition-colors">
                                    Приєднатись
                                </a>
                            @elseif($iv->office_address)
                                <p class="text-xs text-gray-500 text-right max-w-32">{{ $iv->office_address }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-gray-50">
                        <a href="{{ route('seeker.application.detail', $iv->application_id) }}"
                           class="text-xs text-blue-600 hover:underline">
                            Переглянути заявку →
                        </a>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border employer-card-border shadow-sm py-16 text-center text-gray-400">
                    <p class="text-sm">{{ $tab === 'upcoming' ? 'Немає запланованих співбесід' : 'Минулих співбесід немає' }}</p>
                </div>
            @endforelse
        </div>

    </div>
</div>
