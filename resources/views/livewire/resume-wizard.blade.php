<div
    class="resume-wizard min-h-screen bg-gray-50"
    x-data="resumeWizard()"
    x-init="init()"
>
    {{-- STICKY HEADER --}}
    <div class="sticky z-40 bg-white border-b border-gray-200 shadow-sm" style="top: 64px;" id="wizard-sticky-header">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Конструктор резюме</h1>

                {{-- Save indicator --}}
                <div class="text-sm">
                    @if ($isSaving)
                        <span class="inline-flex items-center gap-2 text-amber-600">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            Збереження...
                        </span>
                    @elseif ($saveMessageVisible)
                        <span class="inline-flex items-center gap-2 text-green-600">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            {{ $saveMessage }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MOBILE STEP INDICATOR --}}
    <div class="lg:hidden bg-white border-b border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Прогрес</span>
            <span class="text-xs font-semibold text-blue-600">{{ $currentStep }} / {{ $totalSteps }}</span>
        </div>
        <div class="flex gap-1">
            @for ($i = 1; $i <= $totalSteps; $i++)
                <div class="h-1.5 flex-1 rounded-full {{ $i < $currentStep ? 'bg-green-500' : ($i === $currentStep ? 'bg-blue-500' : 'bg-gray-200') }}"></div>
            @endfor
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

            {{-- MAIN CONTENT --}}
            <main class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow">

                    {{-- Step views --}}
                    @if ($currentStep === 1)
                        @livewire('resume-steps.card-step', [
                            'resume'   => $resume,
                            'formData' => $formData,
                        ], key('step-1-' . $resume->id))
                    @endif

                    @if ($currentStep === 2)
                        @livewire('resume-steps.auth-step', [
                            'resume'   => $resume,
                            'formData' => $formData,
                        ], key('step-2-' . $resume->id))
                    @endif

                    @if ($currentStep === 3)
                        @livewire('resume-steps.experience-step', [
                            'resume'   => $resume,
                            'formData' => $formData,
                        ], key('step-3-' . $resume->id))
                    @endif

                    @if ($currentStep === 4)
                        @livewire('resume-steps.skills-step', [
                            'resume'   => $resume,
                            'formData' => $formData,
                        ], key('step-4-' . $resume->id))
                    @endif

                    @if ($currentStep === 5)
                        @livewire('resume-steps.location-step', [
                            'resume'   => $resume,
                            'formData' => $formData,
                        ], key('step-5-' . $resume->id))
                    @endif

                    @if ($currentStep === 6)
                        @livewire('resume-steps.notifications-step', [
                            'resume'   => $resume,
                            'formData' => $formData,
                        ], key('step-6-' . $resume->id))
                    @endif

                    {{-- FOOTER BUTTONS --}}
                    <div class="px-6 py-6 border-t border-gray-200 flex items-center justify-between gap-4">
                        <button
                            wire:click="previousStep"
                            @disabled($currentStep === 1)
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            ← Назад
                        </button>

                        <span class="text-sm text-gray-500">
                            Крок {{ $currentStep }} з {{ $totalSteps }}
                        </span>

                        @if ($currentStep < $totalSteps)
                            <button
                                wire:click="nextStep"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                            >
                                Далі →
                            </button>
                        @endif
                    </div>

                    {{-- Validation errors --}}
                    @if (!empty($validationErrors))
                        <div class="px-6 py-4 bg-red-50 border border-red-200 rounded-b-lg">
                            @foreach ($validationErrors as $message)
                                <p class="text-sm text-red-700">{{ $message }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>
            </main>

            {{-- SIDEBAR — STEPPER (desktop only) --}}
            <aside class="lg:col-span-1">
                @livewire('resume-stepper', [
                    'resume'        => $resume,
                    'currentStep'   => $currentStep,
                    'stepperStatus' => $stepperStatus,
                    'isPublishable' => $resume->isPublishable(),
                ], key('stepper-' . $resume->id . '-' . $currentStep))
            </aside>
        </div>
    </div>
</div>

@script
<script>
    function resumeWizard() {
        return {
            autoSaveTimer: null,

            init() {
                this.updateStickyTop();
                window.addEventListener('resize', () => this.updateStickyTop());

                $wire.on('scheduleAutoSave', () => {
                    clearTimeout(this.autoSaveTimer);
                    this.autoSaveTimer = setTimeout(() => {
                        $wire.saveChanges();
                    }, 2500);
                });

                $wire.on('hideSaveMessage', () => {
                    setTimeout(() => {
                        $wire.hideSaveMessage();
                    }, 2000);
                });
            },

            updateStickyTop() {
                const el = document.getElementById('wizard-sticky-header');
                if (el) el.style.top = window.innerWidth < 768 ? '64px' : '120px';
            },
        };
    }
</script>
@endscript
