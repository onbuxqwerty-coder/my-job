<div class="px-6 py-6 space-y-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Ваша картка-візитка</h2>
        <p class="text-sm text-gray-600 mt-1">Основна інформація про вас для роботодавців</p>
    </div>

    {{-- Ім'я --}}
    <div>
        <label class="block text-sm font-semibold text-gray-900 mb-2">Ім'я</label>
        <input
            type="text"
            wire:model.live.debounce.2500ms="formData.personal_info.first_name"
            placeholder="Наприклад: Іван"
            class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                {{ isset($errors['first_name']) ? 'border-red-500' : 'border-gray-300' }}"
        />
        @if (isset($errors['first_name']))
            <p class="mt-1 text-sm text-red-600">{{ $errors['first_name'] }}</p>
        @endif
    </div>

    {{-- По батькові --}}
    <div>
        <label class="block text-sm font-semibold text-gray-900 mb-2">По батькові <span class="text-gray-400 font-normal">(необов'язково)</span></label>
        <input
            type="text"
            wire:model.live.debounce.2500ms="formData.personal_info.patronymic"
            placeholder="Наприклад: Іванович"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
    </div>

    {{-- Прізвище --}}
    <div>
        <label class="block text-sm font-semibold text-gray-900 mb-2">Прізвище</label>
        <input
            type="text"
            wire:model.live.debounce.2500ms="formData.personal_info.last_name"
            placeholder="Наприклад: Петренко"
            class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                {{ isset($errors['last_name']) ? 'border-red-500' : 'border-gray-300' }}"
        />
        @if (isset($errors['last_name']))
            <p class="mt-1 text-sm text-red-600">{{ $errors['last_name'] }}</p>
        @endif
    </div>

    {{-- Телефон --}}
    <div
        x-data="{
            init() {
                const el = this.$refs.phone;
                const raw = el.value.replace(/\D/g, '');
                if (raw) el.value = this.buildMask(raw);
                else if (!el.value) el.value = '+38 (0';
            },
            buildMask(digits) {
                // normalize: strip leading 380 / 38 / 0
                if (digits.startsWith('380')) digits = digits.slice(3);
                else if (digits.startsWith('38')) digits = digits.slice(2);
                else if (digits.startsWith('0')) digits = digits.slice(1);
                digits = digits.slice(0, 9);

                let out = '+38 (0';
                if (digits.length > 0) out += digits.slice(0, Math.min(2, digits.length));
                if (digits.length >= 2) out += ') ';
                if (digits.length > 2) out += digits.slice(2, Math.min(5, digits.length));
                if (digits.length >= 5) out += '-';
                if (digits.length > 5) out += digits.slice(5, Math.min(7, digits.length));
                if (digits.length >= 7) out += '-';
                if (digits.length > 7) out += digits.slice(7, 9);
                return out;
            },
            onInput(e) {
                const pos = e.target.selectionStart;
                const prev = e.target.value;
                let digits = prev.replace(/\D/g, '');
                const masked = this.buildMask(digits);
                e.target.value = masked;
                // sync to Livewire
                \$wire.set('formData.personal_info.phone', masked);
            },
            onFocus(e) {
                if (!e.target.value) e.target.value = '+38 (0';
            },
            onKeydown(e) {
                // prevent erasing the prefix '+38 (0'
                const prefix = '+38 (0';
                if ((e.key === 'Backspace' || e.key === 'Delete') && e.target.value.length <= prefix.length) {
                    e.preventDefault();
                    e.target.value = prefix;
                }
            }
        }"
    >
        <label class="block text-sm font-semibold text-gray-900 mb-2">Телефон</label>
        <input
            x-ref="phone"
            type="tel"
            wire:model.live.debounce.2500ms="formData.personal_info.phone"
            placeholder="+38 (0XX) XXX-XX-XX"
            autocomplete="tel"
            @input="onInput($event)"
            @focus="onFocus($event)"
            @keydown="onKeydown($event)"
            class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                {{ isset($errors['phone']) ? 'border-red-500' : 'border-gray-300' }}"
        />
        @if (isset($errors['phone']))
            <p class="mt-1 text-sm text-red-600">{{ $errors['phone'] }}</p>
        @endif
    </div>

    {{-- Видимість --}}
    <div class="space-y-3 pt-4 border-t border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900">Видимість резюме</h3>

        <label class="flex items-center gap-3 cursor-pointer">
            <input
                type="checkbox"
                wire:model="formData.personal_info.privacy"
                wire:change="updatePrivacy"
                class="w-4 h-4 rounded border-gray-300"
            />
            <span class="text-sm text-gray-700">
                <span class="font-semibold">Приватність</span>
                <span class="text-gray-500"> — розміщуйте резюме анонімно</span>
            </span>
        </label>

        <label class="flex items-center gap-3 cursor-pointer">
            <input
                type="checkbox"
                wire:model="formData.personal_info.transparency"
                wire:change="updateTransparency"
                class="w-4 h-4 rounded border-gray-300"
            />
            <span class="text-sm text-gray-700">
                <span class="font-semibold">Прозорість</span>
                <span class="text-gray-500"> — компанії бачать ваші переглади вакансій</span>
            </span>
        </label>
    </div>

    <div class="mj-alert p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <strong>Порада:</strong> Точні дані допомагають роботодавцям краще зрозуміти вас та запропонувати релевантні позиції.
        </p>
    </div>
</div>
