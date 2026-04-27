<?php

declare(strict_types=1);

use App\Models\Vacancy;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Vacancy $vacancy;

    public function mount(Vacancy $vacancy): void
    {
        abort_unless($vacancy->company_id === auth()->user()->company?->id, 403);
        abort_if($vacancy->status->value === 'archived', 403, 'Архівовану вакансію не можна продовжити.');

        $this->vacancy = $vacancy;
    }

    public function plans(): array
    {
        $prices = config('payments.prices', []);
        $vacancy = $this->vacancy;

        $base = $vacancy->status->value === 'expired'
            ? now()
            : ($vacancy->expires_at ?? now());

        return [
            15 => [
                'days'           => 15,
                'label'          => '15 днів',
                'price_uah'      => ($prices[15] ?? 0) / 100,
                'description'    => 'Підходить для термінових позицій',
                'highlight'      => false,
                'new_expires_at' => $base->copy()->addDays(15),
            ],
            30 => [
                'days'           => 30,
                'label'          => '30 днів',
                'price_uah'      => ($prices[30] ?? 0) / 100,
                'description'    => 'Найпопулярніший вибір',
                'highlight'      => true,
                'new_expires_at' => $base->copy()->addDays(30),
            ],
            90 => [
                'days'           => 90,
                'label'          => '90 днів',
                'price_uah'      => ($prices[90] ?? 0) / 100,
                'description'    => 'Найвигідніша ціна за день',
                'highlight'      => false,
                'new_expires_at' => $base->copy()->addDays(90),
            ],
        ];
    }
}
?>

<div>
    <x-employer-tabs />

    <div class="max-w-3xl mx-auto px-4 py-8">

        {{-- Заголовок --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Продовжити публікацію</h1>
            <p class="mt-1 text-gray-600">
                «{{ $vacancy->title }}»
                @if($vacancy->is_active)
                    · зараз активна, завершується {{ $vacancy->expires_at?->locale('uk')->isoFormat('D MMMM') }}
                @elseif($vacancy->status->value === 'expired')
                    · <span class="text-red-600 font-medium">публікація завершена</span>
                @endif
            </p>
        </div>

        @if(session('error'))
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-6 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-yellow-700 text-sm">
                {{ session('warning') }}
            </div>
        @endif

        {{-- Картки тарифів --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            @foreach($this->plans() as $plan)
                <div class="relative rounded-xl border-2 bg-white p-6
                    {{ $plan['highlight'] ? 'border-blue-500 shadow-md' : 'border-gray-200' }}">

                    @if($plan['highlight'])
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="bg-blue-500 text-white text-xs font-semibold px-3 py-1 rounded-full">
                                Найпопулярніший
                            </span>
                        </div>
                    @endif

                    <div class="text-center">
                        <p class="text-3xl font-bold text-gray-900">{{ $plan['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold text-blue-600">
                            {{ number_format($plan['price_uah'], 0, '.', ' ') }} ₴
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ number_format($plan['price_uah'] / $plan['days'], 1, '.', ' ') }} ₴/день
                        </p>
                        <p class="mt-3 text-sm text-gray-600">{{ $plan['description'] }}</p>
                        <p class="mt-3 text-xs text-gray-400">
                            Активна до {{ $plan['new_expires_at']->locale('uk')->isoFormat('D MMMM YYYY') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('employer.vacancies.extend.initiate', $vacancy) }}" class="mt-6">
                        @csrf
                        <input type="hidden" name="days" value="{{ $plan['days'] }}">
                        <button type="submit"
                            class="w-full py-2.5 px-4 rounded-lg text-sm font-medium transition-colors
                                {{ $plan['highlight']
                                    ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                    : 'bg-gray-100 hover:bg-gray-200 text-gray-800' }}">
                            Оплатити {{ number_format($plan['price_uah'], 0) }} ₴
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        {{-- Безпека --}}
        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <p class="text-sm text-gray-600">
                    <span class="font-medium text-gray-800">Безпечна оплата</span> через
                    @switch(config('payments.default'))
                        @case('mono') MonoPay (Monobank Acquiring) @break
                        @case('wayforpay') WayForPay @break
                        @case('liqpay') LiqPay (ПриватБанк) @break
                        @case('stripe') Stripe @break
                        @default {{ ucfirst(config('payments.default', '—')) }}
                    @endswitch.
                    Картка не зберігається на нашому сервері.
                </p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('employer.vacancies.edit', $vacancy->id) }}"
               class="text-sm text-gray-500 hover:text-gray-700">
                ← Повернутись до вакансії
            </a>
        </div>
    </div>
</div>
