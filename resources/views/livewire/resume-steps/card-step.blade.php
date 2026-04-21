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
            wire:blur="onBlur"
            placeholder="Наприклад: Іван"
            class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                {{ isset($errors['first_name']) ? 'border-red-500' : 'border-gray-300' }}"
        />
        @if (isset($errors['first_name']))
            <p class="mt-1 text-sm text-red-600">{{ $errors['first_name'] }}</p>
        @endif
    </div>

    {{-- Прізвище --}}
    <div>
        <label class="block text-sm font-semibold text-gray-900 mb-2">Прізвище</label>
        <input
            type="text"
            wire:model.live.debounce.2500ms="formData.personal_info.last_name"
            wire:blur="onBlur"
            placeholder="Наприклад: Петренко"
            class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                {{ isset($errors['last_name']) ? 'border-red-500' : 'border-gray-300' }}"
        />
        @if (isset($errors['last_name']))
            <p class="mt-1 text-sm text-red-600">{{ $errors['last_name'] }}</p>
        @endif
    </div>

    {{-- Видимість --}}
    <div class="space-y-3 pt-4 border-t border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900">Видимість резюме</h3>

        <label class="flex items-center gap-3 cursor-pointer">
            <input
                type="checkbox"
                wire:model="formData.personal_info.privacy"
                wire:change="updatePrivacy($event.target.checked)"
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
                wire:change="updateTransparency($event.target.checked)"
                class="w-4 h-4 rounded border-gray-300"
            />
            <span class="text-sm text-gray-700">
                <span class="font-semibold">Прозорість</span>
                <span class="text-gray-500"> — компанії бачать ваші переглади вакансій</span>
            </span>
        </label>
    </div>

    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-800">
            <strong>Порада:</strong> Точні дані допомагають роботодавцям краще зрозуміти вас та запропонувати релевантні позиції.
        </p>
    </div>
</div>
