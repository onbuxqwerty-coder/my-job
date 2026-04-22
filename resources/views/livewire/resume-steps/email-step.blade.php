<div class="px-6 py-6 space-y-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Верифікація email</h2>
        <p class="text-sm text-gray-600 mt-1">Ми надішлемо код для перевірки вашої адреси</p>
    </div>

    @if ($isVerified)
        {{-- Verified state --}}
        <div class="p-4 rounded-lg" style="background-color: #1F2937; border: 1px solid #4B5563;">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: #34D399;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-semibold" style="color: #D1FAE5;">Email верифіковано</p>
                    <p class="text-sm" style="color: #A7F3D0;">{{ $email }}</p>
                </div>
            </div>
            <button wire:click="changeEmail" class="mt-3 text-sm font-semibold underline" style="color: #6EE7B7;" onmouseover="this.style.color='#D1FAE5'" onmouseout="this.style.color='#6EE7B7'">
                Змінити email
            </button>
        </div>

    @elseif (!$codeSent)
        {{-- Step 1: Enter email --}}
        <div>
            <label class="block text-sm font-semibold text-gray-900 mb-2">Ваш email</label>
            <input
                type="email"
                wire:model="email"
                placeholder="your@email.com"
                class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                    {{ isset($errors['email']) ? 'border-red-500' : 'border-gray-300' }}"
            />
            @if (isset($errors['email']))
                <p class="mt-1 text-sm text-red-600">{{ $errors['email'] }}</p>
            @endif
        </div>

        <button
            wire:click="sendVerificationCode"
            wire:loading.attr="disabled"
            class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition disabled:opacity-60"
        >
            <span wire:loading wire:target="sendVerificationCode">Надсилання...</span>
            <span wire:loading.remove wire:target="sendVerificationCode">Надіслати код</span>
        </button>

    @else
        {{-- Step 2: Enter code --}}
        <div class="mj-alert p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-900">
                Код відправлено на <strong>{{ $email }}</strong>
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-900 mb-2">Код верифікації (6 цифр)</label>
            <input
                type="text"
                wire:model="verificationCode"
                maxlength="6"
                inputmode="numeric"
                placeholder="000000"
                class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                    tracking-widest text-center text-2xl font-bold
                    {{ isset($errors['code']) ? 'border-red-500' : 'border-gray-300' }}"
            />
            @if (isset($errors['code']))
                <p class="mt-1 text-sm text-red-600">{{ $errors['code'] }}</p>
            @endif
        </div>

        <button
            wire:click="verifyEmail"
            wire:loading.attr="disabled"
            class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition disabled:opacity-60"
        >
            <span wire:loading wire:target="verifyEmail">Перевірка...</span>
            <span wire:loading.remove wire:target="verifyEmail">Підтвердити код</span>
        </button>

        <div class="text-center">
            @if ($countdown > 0)
                <p class="text-sm text-gray-500">Повторити через {{ $countdown }} сек.</p>
            @else
                <button wire:click="sendVerificationCode" class="text-sm text-blue-600 hover:text-blue-700 font-semibold">
                    Надіслати код ще раз
                </button>
            @endif
        </div>

        <button wire:click="changeEmail" class="w-full text-sm text-gray-600 hover:text-gray-900 underline">
            Змінити email
        </button>
    @endif

    <div class="mj-alert p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <strong>Важливо:</strong> Email дійсно впливає на можливість отримання пропозицій від роботодавців.
        </p>
    </div>
</div>

@script
<script>
    $wire.on('start-countdown', (data) => {
        let remaining = data.duration;
        const timer = setInterval(() => {
            remaining--;
            $wire.dispatch('tick-countdown', { remaining });
            if (remaining <= 0) clearInterval(timer);
        }, 1000);
    });
</script>
@endscript
