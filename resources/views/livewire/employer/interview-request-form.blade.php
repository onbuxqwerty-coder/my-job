<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\InterviewRequest;
use App\Services\AsyncInterviewService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public int $applicationId;

    /** @var array<string> */
    public array $questions = [''];

    public string $deadlineAt = '';

    public string $sent = '';

    public function mount(int $applicationId): void
    {
        $this->applicationId = $applicationId;

        abort_unless(
            Application::whereHas('vacancy', fn ($q) => $q->where('company_id', auth()->user()->company->id))
                ->where('id', $applicationId)
                ->exists(),
            403
        );
    }

    #[Computed]
    public function existingRequest(): ?InterviewRequest
    {
        return InterviewRequest::where('application_id', $this->applicationId)->first();
    }

    public function addQuestion(): void
    {
        if (count($this->questions) < 5) {
            $this->questions[] = '';
        }
    }

    public function removeQuestion(int $index): void
    {
        if (count($this->questions) > 1) {
            array_splice($this->questions, $index, 1);
            $this->questions = array_values($this->questions);
        }
    }

    public function send(AsyncInterviewService $service): void
    {
        $this->validate([
            'questions'   => 'required|array|min:1|max:5',
            'questions.*' => 'required|string|min:10',
            'deadlineAt'  => 'nullable|date|after:today',
        ], [
            'questions.*.min' => 'Кожне питання має містити щонайменше 10 символів.',
        ]);

        $application = Application::findOrFail($this->applicationId);
        $deadline    = $this->deadlineAt ? Carbon::parse($this->deadlineAt) : null;

        try {
            $service->send($application, $this->questions, $deadline);
            $this->sent = 'ok';
            unset($this->existingRequest);
        } catch (\RuntimeException $e) {
            $this->addError('questions', $e->getMessage());
        }
    }
}; ?>

<div class="space-y-4">
    @if($this->existingRequest)
        {{-- Запит вже надіслано --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Статус інтерв'ю</h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">
                {{ $this->existingRequest->status->label() }}
            </span>
            @if($this->existingRequest->deadline_at)
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Дедлайн: {{ $this->existingRequest->deadline_at->format('d.m.Y') }}
                </p>
            @endif
        </div>
    @elseif($sent === 'ok')
        <div class="rounded-xl border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 p-5">
            <p class="text-sm font-medium text-green-700 dark:text-green-400">
                Інтерв'ю надіслано кандидату.
            </p>
        </div>
    @else
        <form wire:submit="send" class="space-y-4">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">Надіслати інтерв'ю</h3>

            {{-- Питання --}}
            <div class="space-y-3">
                @foreach($questions as $i => $question)
                    <div class="flex gap-2 items-start" wire:key="question-{{ $i }}">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Питання {{ $i + 1 }}
                            </label>
                            <textarea wire:model="questions.{{ $i }}"
                                      rows="2"
                                      placeholder="Введіть питання (мін. 10 символів)..."
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                             bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm
                                             focus:ring-2 focus:ring-orange-400 focus:border-transparent transition resize-none">
                            </textarea>
                            @error("questions.{$i}")
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @if(count($questions) > 1)
                            <button type="button"
                                    wire:click="removeQuestion({{ $i }})"
                                    class="mt-6 text-gray-400 hover:text-red-500 transition text-lg leading-none">
                                ×
                            </button>
                        @endif
                    </div>
                @endforeach

                @if(count($questions) < 5)
                    <button type="button"
                            wire:click="addQuestion"
                            class="text-sm text-orange-500 hover:text-orange-600 font-medium transition">
                        + Додати питання
                    </button>
                @endif
            </div>

            {{-- Дедлайн --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Дедлайн <span class="text-gray-400">(необов'язково)</span>
                </label>
                <input type="date"
                       wire:model="deadlineAt"
                       class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                              bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm
                              focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                @error('deadlineAt')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            @error('questions')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror

            {{-- Дії --}}
            <div class="flex items-center gap-3 pt-1">
                <button type="button"
                        wire:click="$dispatch('close-interview-form')"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">
                    Скасувати
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-5 py-2 bg-orange-500 hover:bg-orange-600 disabled:opacity-60
                               text-white text-sm font-bold rounded-lg transition">
                    <span wire:loading.remove wire:target="send">Надіслати інтерв'ю</span>
                    <span wire:loading wire:target="send">Надсилання...</span>
                </button>
            </div>
        </form>
    @endif
</div>
