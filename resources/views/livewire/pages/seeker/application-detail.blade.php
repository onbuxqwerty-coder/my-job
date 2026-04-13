<?php

declare(strict_types=1);

use App\Enums\InterviewStatus;
use App\Models\Application;
use App\Models\Interview;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public int $applicationId;

    public function mount(int $applicationId): void
    {
        $this->applicationId = $applicationId;

        // Ensure application belongs to current user
        abort_unless(
            Application::where('id', $applicationId)->where('user_id', auth()->id())->exists(),
            403
        );
    }

    #[Computed]
    public function application(): Application
    {
        return Application::with([
            'vacancy.company',
            'vacancy.city',
            'vacancy.category',
            'interviews',
            'notes.author',
            'messages',
        ])->where('user_id', auth()->id())->findOrFail($this->applicationId);
    }

    #[Computed]
    public function upcomingInterview(): ?Interview
    {
        return $this->application->interviews
            ->where('status', InterviewStatus::Scheduled)
            ->where('scheduled_at', '>=', now())
            ->sortBy('scheduled_at')
            ->first();
    }
}; ?>

<div class="min-h-screen bg-gray-50">
    <x-seeker-tabs />

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Back --}}
        <a href="{{ route('seeker.applications') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Назад до заявок
        </a>

        @php
            $app = $this->application;
            $vacancy = $app->vacancy;
            $company = $vacancy->company;
            $statusColors = [
                'pending'   => ['bg' => '#f3f4f6', 'color' => '#374151'],
                'screening' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                'interview' => ['bg' => '#fef3c7', 'color' => '#b45309'],
                'hired'     => ['bg' => '#dcfce7', 'color' => '#15803d'],
                'rejected'  => ['bg' => '#fee2e2', 'color' => '#b91c1c'],
            ];
            $sc = $statusColors[$app->status->value] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];

            $steps = [
                ['status' => 'pending',   'label' => 'Подано'],
                ['status' => 'screening', 'label' => 'Розгляд'],
                ['status' => 'interview', 'label' => 'Співбесіда'],
                ['status' => 'hired',     'label' => 'Прийнятий'],
            ];
            $stepValues = array_column($steps, 'status');
            $currentIdx = array_search($app->status->value, $stepValues);
            $isRejected = $app->status->value === 'rejected';
        @endphp

        {{-- Header card --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-xl bg-blue-50 border border-gray-100 flex items-center justify-center font-bold text-blue-600 text-lg shrink-0 overflow-hidden">
                    @if($company->logo)
                        <img src="{{ Storage::disk('public')->url($company->logo) }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-gray-900">{{ $vacancy->title }}</h1>
                    <p class="text-gray-500 mt-0.5">
                        {{ $company->name }}
                        @if($vacancy->city) · {{ $vacancy->city->name }} @endif
                        @if($vacancy->category) · {{ $vacancy->category->name }} @endif
                    </p>
                    @if($vacancy->salary_from || $vacancy->salary_to)
                        <p class="text-sm font-semibold text-gray-700 mt-1">
                            @if($vacancy->salary_from && $vacancy->salary_to)
                                {{ number_format($vacancy->salary_from, 0, '.', ' ') }} – {{ number_format($vacancy->salary_to, 0, '.', ' ') }} {{ $vacancy->currency }}
                            @elseif($vacancy->salary_from)
                                від {{ number_format($vacancy->salary_from, 0, '.', ' ') }} {{ $vacancy->currency }}
                            @else
                                до {{ number_format($vacancy->salary_to, 0, '.', ' ') }} {{ $vacancy->currency }}
                            @endif
                        </p>
                    @endif
                </div>
                <span style="background:{{ $sc['bg'] }}; color:{{ $sc['color'] }};"
                      class="shrink-0 px-3 py-1.5 rounded-full text-sm font-semibold">
                    {{ $app->status->label() }}
                </span>
            </div>

            {{-- Progress bar --}}
            @if(!$isRejected)
                <div class="mt-6">
                    <div class="flex items-center justify-between relative">
                        <div class="absolute top-3.5 left-0 right-0 h-0.5 bg-gray-200 -z-0"></div>
                        @foreach($steps as $i => $step)
                            @php $done = ($currentIdx !== false && $i <= $currentIdx); @endphp
                            <div class="flex flex-col items-center gap-1.5 z-10">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-colors
                                            {{ $done ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-400' }}">
                                    @if($done)
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                                <span class="text-xs {{ $done ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mt-4 p-3 bg-red-50 rounded-xl text-sm text-red-600 font-medium text-center">
                    На жаль, ваша заявка була відхилена
                </div>
            @endif

            <p class="text-xs text-gray-400 mt-4">Подано {{ $app->created_at->format('d.m.Y') }}</p>
        </div>

        {{-- Upcoming interview --}}
        @if($this->upcomingInterview)
            @php $iv = $this->upcomingInterview; @endphp
            <div class="bg-purple-50 border border-purple-200 rounded-2xl p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-purple-800 uppercase tracking-wide mb-1">Запланована співбесіда</p>
                        <p class="text-lg font-bold text-gray-900">{{ $iv->scheduled_at->format('d.m.Y') }} о {{ $iv->scheduled_at->format('H:i') }}</p>
                        <p class="text-sm text-gray-600 mt-0.5">
                            {{ $iv->type->label() }}
                            @if($iv->office_address) · {{ $iv->office_address }} @endif
                        </p>
                        @if($iv->notes)
                            <p class="text-sm text-gray-600 mt-2">{{ $iv->notes }}</p>
                        @endif
                    </div>
                    @if($iv->meeting_link)
                        <a href="{{ $iv->meeting_link }}" target="_blank"
                           class="shrink-0 px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-xl hover:bg-purple-700 transition-colors">
                            Приєднатись
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Messages from employer --}}
        @if($app->messages->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-900">Повідомлення від роботодавця</h2>
                </div>
                @foreach($app->messages as $msg)
                    <div class="px-6 py-4 border-b border-gray-50 last:border-b-0">
                        <p class="text-sm font-semibold text-gray-800 mb-0.5">{{ $msg->subject ?? 'Повідомлення' }}</p>
                        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $msg->body }}</p>
                        <p class="text-xs text-gray-400 mt-2">{{ $msg->created_at->diffForHumans() }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Vacancy description --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Опис вакансії</h2>
            <div class="prose prose-sm max-w-none text-gray-600">
                {!! nl2br(e($vacancy->description)) !!}
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="{{ route('jobs.show', $vacancy->slug) }}" target="_blank"
                   class="text-sm text-blue-600 hover:underline">
                    Переглянути вакансію →
                </a>
            </div>
        </div>

    </div>
</div>
