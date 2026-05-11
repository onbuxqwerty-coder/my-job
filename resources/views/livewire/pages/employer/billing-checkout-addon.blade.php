<?php

declare(strict_types=1);

use App\Enums\AddonType;
use App\Http\Controllers\Payments\PaymentGatewayRegistry;
use App\Payments\CheckoutService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public AddonType $addon;

    public function mount(AddonType $addon): void
    {
        $this->addon = $addon;
    }

    public function pay(string $gateway): void
    {
        $registry = app(PaymentGatewayRegistry::class);
        $gw = $registry->get($gateway);

        abort_if($gw === null, 422, "Невідомий шлюз: {$gateway}");

        $checkout = new CheckoutService($gw);
        $url = $checkout->createAddonCheckout($this->addon, auth()->user());

        $this->redirect($url, navigate: false);
    }
}
?>

<div class="min-h-screen mj-billing-bg">
    <x-employer-tabs />

    <div class="max-w-lg mx-auto px-4 py-12">

        {{-- Addon summary --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6 text-center">
            <p class="text-sm text-gray-500 mb-1">Ви обрали послугу</p>
            <h1 class="text-2xl font-extrabold text-gray-900">{{ $addon->label() }}</h1>
            <p class="text-3xl font-bold text-blue-600 mt-2">
                {{ number_format($addon->price(), 0, '.', ' ') }} ₴
            </p>
            <p class="text-sm text-gray-500 mt-1">Термін дії: {{ $addon->durationDays() }} днів</p>
        </div>

        {{-- Gateway selection --}}
        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3 text-center">Оберіть спосіб оплати</p>

        <div class="flex flex-col gap-3">

            {{-- LiqPay --}}
            <button wire:click="pay('liqpay')" wire:loading.attr="disabled"
                    class="flex items-center gap-4 w-full px-5 py-4 bg-white border border-gray-200 rounded-2xl hover:border-blue-500 hover:shadow-md transition-all text-left">
                <div class="w-10 h-10 rounded-xl bg-[#FF6600] flex items-center justify-center shrink-0">
                    <span class="text-white text-xs font-black">LP</span>
                </div>
                <div>
                    <p class="font-bold text-gray-900">LiqPay</p>
                    <p class="text-xs text-gray-500">Картка, Apple Pay, Google Pay</p>
                </div>
                <svg class="ml-auto w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            {{-- WayForPay --}}
            <button wire:click="pay('wayforpay')" wire:loading.attr="disabled"
                    class="flex items-center gap-4 w-full px-5 py-4 bg-white border border-gray-200 rounded-2xl hover:border-blue-500 hover:shadow-md transition-all text-left">
                <div class="w-10 h-10 rounded-xl bg-[#0066CC] flex items-center justify-center shrink-0">
                    <span class="text-white text-xs font-black">WP</span>
                </div>
                <div>
                    <p class="font-bold text-gray-900">WayForPay</p>
                    <p class="text-xs text-gray-500">Картка, частинами, QR</p>
                </div>
                <svg class="ml-auto w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('employer.billing') }}" class="text-sm text-gray-500 hover:text-gray-700">← Повернутись до тарифів</a>
        </div>

        <div wire:loading class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white border border-gray-200 rounded-2xl px-8 py-6 text-center shadow-xl">
                <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-sm font-medium text-gray-600">Перенаправлення на оплату...</p>
            </div>
        </div>

    </div>
</div>
