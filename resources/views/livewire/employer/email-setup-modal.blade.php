<div>
    <div
        x-show="$wire.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="$wire.skip()"
        style="display:none;"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-black/60"></div>

        <div
            x-show="$wire.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            style="display:none;"
            class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-sm p-8"
        >
            {{-- Step: email --}}
            @if($step === 'email')
                <div class="text-center mb-6">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900 mx-auto mb-4">
                        <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Вкажіть email</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Додайте пошту для отримання сповіщень про відгуки на ваші вакансії
                    </p>
                </div>

                <form wire:submit="sendCode" class="space-y-4">
                    <div>
                        <input
                            type="email"
                            wire:model="email"
                            placeholder="example@company.ua"
                            autocomplete="email"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition"
                        />
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold rounded-xl transition text-sm"
                    >
                        <span wire:loading.remove wire:target="sendCode">Надіслати код</span>
                        <span wire:loading wire:target="sendCode">Надсилаємо...</span>
                    </button>

                    <button
                        type="button"
                        wire:click="skip"
                        class="w-full py-2 text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                    >
                        Пропустити
                    </button>
                </form>

            {{-- Step: code --}}
            @else
                <div class="text-center mb-6">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-green-100 dark:bg-green-900 mx-auto mb-4">
                        <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Введіть код</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Код підтвердження надіслано на<br>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $email }}</span>
                    </p>
                </div>

                <form wire:submit="verify" class="space-y-4">
                    <div>
                        <input
                            type="text"
                            wire:model="code"
                            placeholder="000000"
                            maxlength="6"
                            autocomplete="one-time-code"
                            inputmode="numeric"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-center tracking-[0.3em] font-mono transition"
                        />
                        @error('code')
                            <p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold rounded-xl transition text-sm"
                    >
                        <span wire:loading.remove wire:target="verify">Підтвердити</span>
                        <span wire:loading wire:target="verify">Перевіряємо...</span>
                    </button>

                    <div class="flex justify-between text-xs text-gray-400 pt-1">
                        <button type="button" wire:click="resend" class="hover:text-blue-600 transition">
                            Змінити email
                        </button>
                        <button type="button" wire:click="skip" class="hover:text-gray-600 transition">
                            Пропустити
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
