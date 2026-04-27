<div>
    @if($this->isConfirmed)
        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-center">
            <p class="font-semibold text-green-800">✅ Вакансію продовжено!</p>
            <p class="text-sm text-green-700 mt-1">
                Активна до {{ $vacancy->expires_at->locale('uk')->isoFormat('D MMMM YYYY, HH:mm') }}
            </p>
            <a href="{{ route('employer.vacancies.edit', $vacancy->id) }}"
               class="mt-4 inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                Переглянути вакансію →
            </a>
        </div>

    @elseif($this->isTimeout)
        <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-center">
            <p class="font-semibold text-yellow-800">⏳ Обробка займає більше часу</p>
            <p class="text-sm text-yellow-700 mt-1">
                Перевірте стан вакансії через кілька хвилин або зверніться до підтримки.
            </p>
            <a href="{{ route('employer.vacancies.edit', $vacancy->id) }}"
               class="mt-4 inline-block text-sm text-yellow-700 underline">
                Перейти до вакансії
            </a>
        </div>

    @else
        <div wire:poll.5s="refresh" class="text-center">
            <div class="inline-flex items-center gap-2 text-gray-500">
                <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm">Очікуємо підтвердження від провайдера...</span>
            </div>
        </div>
    @endif
</div>
