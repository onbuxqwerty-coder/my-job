<?php

declare(strict_types=1);

use App\DTOs\ApplyDTO;
use App\Models\Vacancy;
use App\Services\ApplicationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
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

    public function mount(Vacancy $vacancy): void
    {
        $this->vacancy = $vacancy->load(['company', 'category']);

        if (auth()->check()) {
            $this->alreadyApplied = app(ApplicationService::class)
                ->alreadyApplied(auth()->user(), $vacancy);
        }
    }

    public function apply(): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $key = 'apply:' . auth()->id() . ':' . $this->vacancy->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('resume', 'Too many attempts. Please try again later.');
            return;
        }

        RateLimiter::hit($key, 300);

        $this->validate([
            'resume'       => 'required|file|mimes:pdf,doc,docx|max:5120',
            'coverLetter'  => 'nullable|string|max:5000',
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

            $this->submitted = true;
        } catch (\DomainException $e) {
            $this->addError('resume', $e->getMessage());
        }
    }
}; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Back link --}}
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to all jobs
        </a>

        <div class="flex flex-col lg:flex-row gap-8">

            {{-- Main --}}
            <article class="flex-1 bg-white rounded-2xl border border-gray-200 p-8">

                {{-- Header --}}
                <div class="flex items-start gap-5 mb-8">
                    <div class="w-16 h-16 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0">
                        @if($vacancy->company->logo)
                            <img src="{{ $vacancy->company->logo }}" alt="{{ $vacancy->company->name }}" class="w-full h-full object-cover rounded-xl"/>
                        @else
                            <span class="text-2xl font-bold text-gray-400">
                                {{ strtoupper(substr($vacancy->company->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $vacancy->title }}</h1>
                        <p class="text-gray-500 mt-0.5">{{ $vacancy->company->name }} · {{ $vacancy->company->location }}</p>
                    </div>
                </div>

                {{-- Tags --}}
                <div class="flex flex-wrap gap-2 mb-8">
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 capitalize">
                        {{ str_replace('-', ' ', $vacancy->employment_type->value) }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                        {{ $vacancy->category->name }}
                    </span>
                    @if($vacancy->salary_from)
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-50 text-green-700">
                            {{ number_format($vacancy->salary_from) }}
                            @if($vacancy->salary_to)– {{ number_format($vacancy->salary_to) }}@endif
                            {{ $vacancy->currency }}
                        </span>
                    @endif
                    @if($vacancy->published_at)
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-50 text-gray-400">
                            {{ $vacancy->published_at->diffForHumans() }}
                        </span>
                    @endif
                </div>

                {{-- Description --}}
                <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed">
                    {!! nl2br(e($vacancy->description)) !!}
                </div>

            </article>

            {{-- Apply Sidebar --}}
            <aside class="w-full lg:w-80 shrink-0 space-y-4">

                {{-- Company Card --}}
                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">About Company</h3>
                    <p class="text-sm text-gray-600 mb-3">{{ Str::limit($vacancy->company->description, 120) }}</p>
                    @if($vacancy->company->website)
                        <a href="{{ $vacancy->company->website }}" target="_blank" rel="noopener noreferrer"
                           class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Website
                        </a>
                    @endif
                </div>

                {{-- Apply Form --}}
                <div class="bg-white rounded-2xl border border-gray-200 p-6">

                    @if($submitted)
                        {{-- Success --}}
                        <div class="text-center py-4">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">Application Sent!</h3>
                            <p class="text-sm text-gray-500">The employer will review your application and get back to you.</p>
                        </div>

                    @elseif($alreadyApplied)
                        {{-- Already applied --}}
                        <div class="text-center py-4">
                            <p class="text-sm text-blue-600 font-medium">✅ You have already applied to this vacancy.</p>
                        </div>

                    @elseif(auth()->check() && auth()->user()->role === \App\Enums\UserRole::Employer)
                        {{-- Employers cannot apply --}}
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-400">Employers cannot apply for vacancies.</p>
                        </div>

                    @elseif(!auth()->check())
                        {{-- Not logged in --}}
                        <p class="text-sm text-gray-600 mb-4 text-center">Sign in to apply for this position.</p>
                        <a href="{{ route('login') }}"
                           class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors">
                            Sign In to Apply
                        </a>

                    @else
                        {{-- Apply Form --}}
                        <h3 class="text-base font-semibold text-gray-900 mb-4">Apply for this Position</h3>

                        <form wire:submit="apply" class="space-y-4">

                            {{-- Resume --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Resume <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="file"
                                    wire:model="resume"
                                    accept=".pdf,.doc,.docx"
                                    class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX — max 5 MB</p>
                                @error('resume')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <div wire:loading wire:target="resume" class="text-xs text-blue-500 mt-1">Uploading...</div>
                            </div>

                            {{-- Cover Letter --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cover Letter</label>
                                <textarea
                                    wire:model="coverLetter"
                                    rows="4"
                                    placeholder="Tell the employer why you are a great fit..."
                                    class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                ></textarea>
                                @error('coverLetter')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors"
                            >
                                <span wire:loading.remove wire:target="apply">Submit Application</span>
                                <span wire:loading wire:target="apply">Submitting...</span>
                            </button>

                        </form>
                    @endif

                </div>

                {{-- Telegram deep link --}}
                <div class="bg-blue-50 rounded-2xl border border-blue-100 p-4 text-center">
                    <p class="text-xs text-blue-600 font-medium">Share via Telegram</p>
                    <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($vacancy->title) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="mt-2 inline-flex items-center gap-1.5 text-sm text-blue-700 hover:underline font-medium">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 13.617l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.942z"/>
                        </svg>
                        Share on Telegram
                    </a>
                </div>

            </aside>
        </div>
    </div>
</div>
