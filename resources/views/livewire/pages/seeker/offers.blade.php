<?php

declare(strict_types=1);

use App\Enums\MessageType;
use App\Models\CandidateMessage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Computed]
    public function offers(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return CandidateMessage::with(['application.vacancy.company', 'application.vacancy.city', 'sender'])
            ->whereHas('application', fn($q) => $q->where('user_id', auth()->id()))
            ->where('type', MessageType::Offer)
            ->latest('sent_at')
            ->paginate(10);
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg">
    <x-seeker-tabs />

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900">Пропозиції від роботодавців</h2>
            <p class="text-sm text-gray-500 mt-1">Офіційні пропозиції про роботу, надіслані роботодавцями</p>
        </div>

        @if($this->offers->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-16 text-center">
                <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-gray-700 font-semibold">Поки немає пропозицій</p>
                <p class="text-sm text-gray-400 mt-1">Подавайте заявки на вакансії — роботодавці зможуть надсилати вам офіційні пропозиції</p>
                <a href="{{ route('home') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">
                    Знайти вакансії →
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($this->offers as $offer)
                    @php $vacancy = $offer->application->vacancy; @endphp
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Пропозиція про роботу
                                    </span>
                                    @if($offer->sent_at)
                                        <span class="text-xs text-gray-400">{{ $offer->sent_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                                <h3 class="mt-1.5 text-base font-bold text-gray-900">{{ $vacancy->title }}</h3>
                                <p class="text-sm text-gray-500">{{ $vacancy->company->name }}</p>
                            </div>
                            @if($vacancy->company->logo_url)
                                <img src="{{ $vacancy->company->logo_url }}" alt="{{ $vacancy->company->name }}"
                                     class="w-12 h-12 rounded-xl border border-gray-100 object-cover flex-shrink-0"/>
                            @else
                                <div class="w-12 h-12 rounded-xl border border-gray-100 bg-gray-50 flex items-center justify-center text-lg font-bold text-gray-400 flex-shrink-0">
                                    {{ strtoupper(substr($vacancy->company->name, 0, 2)) }}
                                </div>
                            @endif
                        </div>

                        @if($offer->subject)
                            <div class="px-6 pt-4 pb-0">
                                <p class="text-sm font-semibold text-gray-700">{{ $offer->subject }}</p>
                            </div>
                        @endif

                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $offer->body }}</p>
                        </div>

                        <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                @if($vacancy->city)
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                            <a href="{{ route('seeker.application.detail', $offer->application_id) }}"
                               class="text-sm font-semibold text-blue-600 hover:underline">
                                Переглянути заявку →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $this->offers->links() }}
            </div>
        @endif
    </div>
</div>
