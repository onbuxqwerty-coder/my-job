<div>
    {{-- Limit Modal --}}
    <div
        x-show="$wire.showLimit"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="$wire.showLimit = false"
        style="display:none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-black/60" @click="$wire.showLimit = false"></div>

        <div
            x-show="$wire.showLimit"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            style="display:none;"
            class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center"
        >
            <button
                type="button"
                @click="$wire.showLimit = false"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 mx-auto mb-5">
                <svg class="w-8 h-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-5V7a4 4 0 00-8 0v4H3a1 1 0 00-1 1v6a2 2 0 002 2h14a2 2 0 002-2v-6a1 1 0 00-1-1h-1V7a4 4 0 00-4-4z"/>
                </svg>
            </div>

            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                Ліміт вакансій вичерпано
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Ваш поточний тариф не дозволяє публікувати більше вакансій.<br>
                Оновіть план, щоб продовжити.
            </p>

            <div class="flex flex-col gap-3">
                <a
                    href="{{ route('employer.billing') }}"
                    class="inline-flex items-center justify-center gap-2 w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition text-sm"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                    </svg>
                    Оновити тариф
                </a>
                <a
                    href="{{ route('employer.billing') }}"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 py-2 transition"
                >
                    Переглянути всі тарифи
                </a>
                <button
                    type="button"
                    @click="$wire.showLimit = false"
                    class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 py-1 transition"
                >
                    Закрити
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Overlay --}}
    <div
        x-show="$wire.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="$wire.show = false"
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/60"
            @click="$wire.show = false"
        ></div>

        {{-- Modal Panel --}}
        <div
            x-show="$wire.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md"
            style="display: none;"
        >
            {{-- Close button --}}
            <button
                type="button"
                @click="$wire.show = false"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                aria-label="Закрити"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="p-6">
                {{-- Header --}}
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        🚀 Швидко розмістити вакансію
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        За 30 секунд без складнощів
                    </p>
                </div>

                {{-- Form --}}
                <form wire:submit="publish" class="space-y-4">

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Назва посади <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="title"
                            placeholder="Наприклад: Senior Developer"
                            autocomplete="off"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                   bg-white dark:bg-gray-800 text-gray-900 dark:text-white
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   placeholder-gray-400 transition"
                        />
                        @error('title')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Категорія <span class="text-red-500">*</span>
                        </label>
                        <select
                            wire:model="category_id"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                   bg-white dark:bg-gray-800 text-gray-900 dark:text-white
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        >
                            <option value="">— Виберіть категорію</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- City --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Місто <span class="text-red-500">*</span>
                        </label>
                        <livewire:city-search wire:model="city_id" />
                        @error('city_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Salary --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Зарплата (від)
                        </label>
                        <input
                            type="number"
                            wire:model="salary_from"
                            placeholder="Від (грн)"
                            min="100"
                            max="999999"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                   bg-white dark:bg-gray-800 text-gray-900 dark:text-white
                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                   placeholder-gray-400 transition"
                        />
                        <p class="text-xs text-gray-400 mt-1">
                            Опціонально. Вакансії зі зарплатою отримують більше відповідей.
                        </p>
                        @error('salary_from')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            @click="$wire.show = false"
                            class="flex-1 px-4 py-2.5 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600
                                   rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition font-medium"
                        >
                            Закрити
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-60
                                   text-white rounded-lg transition font-bold flex items-center justify-center gap-2"
                        >
                            <span wire:loading.remove wire:target="publish">
                                🚀 Розмістити
                            </span>
                            <span wire:loading wire:target="publish" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                Обробка...
                            </span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
