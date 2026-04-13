<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Validate('required|string|min:2|max:100')]
    public string $name = '';

    #[Validate('required|email|max:100')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|numeric|min:1000000000|max:9999999999')]
    public string $telegram_id = '';

    #[Validate('nullable|string|min:8')]
    public string $password = '';

    #[Validate('nullable|string|same:password')]
    public string $password_confirmation = '';

    public bool $saved = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->name        = $user->name;
        $this->email       = $user->email;
        $this->phone       = $user->phone ?? '';
        $this->telegram_id = $user->telegram_id ? (string) $user->telegram_id : '';
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone ?: null,
            'telegram_id' => $this->telegram_id ?: null,
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        auth()->user()->update($data);

        $this->password = '';
        $this->password_confirmation = '';
        $this->saved = true;

        $this->dispatch('profile-saved');
    }
}; ?>

<div class="min-h-screen bg-gray-50">
    <x-seeker-tabs />

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Особисті дані</h2>
            </div>

            <form wire:submit="save" class="p-6 space-y-5">

                @if($saved)
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                         class="p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 font-medium text-center">
                        Збережено
                    </div>
                @endif

                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ім'я</label>
                    <input wire:model="name" type="text"
                           class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Електронна пошта</label>
                    <input wire:model="email" type="email"
                           class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Телефон</label>
                    <input wire:model="phone" type="tel" placeholder="+380XXXXXXXXX"
                           class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Telegram ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Telegram ID</label>
                    <input wire:model="telegram_id" type="text" placeholder="123456789"
                           class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-400">
                        Дізнатись свій ID можна через бот
                        <a href="https://t.me/userinfobot" target="_blank" class="text-blue-500 hover:underline">@userinfobot</a>
                    </p>
                    @error('telegram_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <hr class="border-gray-100">

                {{-- Password --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Новий пароль <span class="text-gray-400 font-normal">(залиште порожнім, щоб не змінювати)</span></label>
                    <input wire:model="password" type="password" placeholder="Мінімум 8 символів"
                           class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Підтвердження пароля</label>
                    <input wire:model="password_confirmation" type="password"
                           class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Submit --}}
                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                        <span wire:loading.remove wire:target="save">Зберегти зміни</span>
                        <span wire:loading wire:target="save">Збереження...</span>
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>
