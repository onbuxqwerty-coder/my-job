<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
            return;
        }

        Auth::user()->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        Дякуємо за реєстрацію! Будь ласка, підтвердіть свою електронну пошту, перейшовши за посиланням, яке ми щойно надіслали. Якщо лист не надійшов — ми можемо надіслати його ще раз.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Нове посилання для підтвердження надіслано на вашу електронну пошту.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            Надіслати повторно
        </x-primary-button>

        <button wire:click="logout" type="submit"
                class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Вийти
        </button>
    </div>
</div>
