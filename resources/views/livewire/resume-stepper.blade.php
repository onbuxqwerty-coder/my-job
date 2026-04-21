<nav class="space-y-2">
    {{-- Title --}}
    <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900">Прогрес</h3>
    </div>

    {{-- Steps --}}
    <div class="px-2 py-4 space-y-1">
        @foreach ($steps as $stepNumber => $step)
            @php
                $isCurrentStep = $currentStep === $stepNumber;
                $stepStatus    = $stepStatuses[$stepNumber] ?? [];
                $isCompleted   = $stepStatus['completed']   ?? false;
                $hasErrors     = $stepStatus['hasErrors']   ?? false;
            @endphp

            <button
                wire:click="goToStep({{ $stepNumber }})"
                aria-label="Перейти на крок {{ $stepNumber }}: {{ $step['title'] }}"
                aria-current="{{ $isCurrentStep ? 'step' : 'false' }}"
                class="w-full text-left px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400
                    {{ $isCurrentStep
                        ? 'bg-blue-50 border-2 border-blue-500 shadow-sm'
                        : 'border-2 border-transparent hover:bg-gray-50' }}"
            >
                <div class="flex items-start gap-3">

                    {{-- Status indicator --}}
                    <div class="relative flex-shrink-0 mt-0.5 w-6 h-6">
                        @if ($isCompleted)
                            <div class="w-6 h-6 bg-green-600 rounded-full flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @elseif ($hasErrors)
                            <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @else
                            <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                        @endif

                        @if ($isCurrentStep)
                            <div class="absolute inset-0 rounded-full border-2 border-blue-400 animate-pulse pointer-events-none"></div>
                        @endif
                    </div>

                    {{-- Step text --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold {{ $isCurrentStep ? 'text-blue-900' : 'text-gray-900' }}">
                            {{ $step['title'] }}
                        </p>
                        <p class="text-xs {{ $isCurrentStep ? 'text-blue-700' : 'text-gray-500' }}">
                            {{ $step['description'] }}
                        </p>
                        @if ($hasErrors)
                            <p class="mt-0.5 text-xs text-red-600 font-medium">Потребує уваги</p>
                        @endif
                    </div>

                    {{-- Arrow for current step --}}
                    @if ($isCurrentStep)
                        <div class="flex-shrink-0 self-center">
                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @endif

                </div>
            </button>
        @endforeach
    </div>

    {{-- Legend --}}
    <div class="px-4 py-4 border-t border-gray-200 bg-gray-50 rounded-lg">
        <p class="text-xs font-semibold text-gray-900 mb-2">Статус резюме:</p>
        <ul class="space-y-1 text-xs text-gray-600">
            <li class="flex items-center gap-2">
                <span class="w-3 h-3 bg-green-600 rounded-full inline-block"></span> Заповнено
            </li>
            <li class="flex items-center gap-2">
                <span class="w-3 h-3 bg-red-600 rounded-full inline-block"></span> Помилка
            </li>
            <li class="flex items-center gap-2">
                <span class="w-3 h-3 bg-gray-400 rounded-full inline-block"></span> Не заповнено
            </li>
        </ul>
    </div>

    {{-- Quick actions --}}
    <div class="px-4 py-4 border-t border-gray-200 space-y-2">
        <button
            wire:click="$parent.publishResume"
            @disabled(!$isPublishable)
            class="w-full px-4 py-2 rounded-lg font-medium transition-colors
                {{ $isPublishable
                    ? 'bg-green-600 text-white hover:bg-green-700'
                    : 'bg-gray-200 text-gray-500 cursor-not-allowed' }}"
        >
            Опублікувати
        </button>

        <button
            wire:click="$parent.deleteResume"
            @disabled($resume->status === 'published')
            class="w-full px-4 py-2 rounded-lg font-medium transition-colors border border-red-300 text-red-600 hover:bg-red-50
                {{ $resume->status === 'published' ? 'opacity-50 cursor-not-allowed' : '' }}"
        >
            Видалити чорновик
        </button>
    </div>
</nav>
