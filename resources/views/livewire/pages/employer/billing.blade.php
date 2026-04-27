<?php

declare(strict_types=1);

use App\Models\Vacancy;
use App\Payments\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    private function vacancyIds(): array
    {
        $companyId = auth()->user()->company?->id;
        if (! $companyId) {
            return [];
        }
        return Vacancy::where('company_id', $companyId)->pluck('id')->toArray();
    }

    #[Computed]
    public function transactions(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $vacancyIds = $this->vacancyIds();

        if (empty($vacancyIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        $rawPage = DB::table('payment_processed_events')
            ->where(function ($q) use ($vacancyIds) {
                foreach ($vacancyIds as $vid) {
                    $q->orWhere('order_id', 'LIKE', "vac_{$vid}_%");
                }
            })
            ->orderByDesc('processed_at')
            ->paginate(20);

        $vacancyTitles = Vacancy::whereIn('id', $vacancyIds)->pluck('title', 'id');

        $items = collect($rawPage->items())->map(function ($row) use ($vacancyTitles) {
            [$vacancyId, $days] = CheckoutService::parseOrderId($row->order_id);
            $amountUah = $days ? (config("payments.prices.{$days}") ?? 0) / 100 : 0;

            return (object) [
                'event_id'      => $row->event_id,
                'gateway'       => $row->gateway,
                'gateway_label' => match ($row->gateway) {
                    'mono'      => 'MonoPay',
                    'wayforpay' => 'WayForPay',
                    'liqpay'    => 'LiqPay',
                    'stripe'    => 'Stripe',
                    default     => ucfirst($row->gateway),
                },
                'gateway_class' => match ($row->gateway) {
                    'mono'      => 'bg-green-100 text-green-700',
                    'wayforpay' => 'bg-blue-100 text-blue-700',
                    'liqpay'    => 'bg-yellow-100 text-yellow-700',
                    'stripe'    => 'bg-purple-100 text-purple-700',
                    default     => 'bg-gray-100 text-gray-600',
                },
                'vacancy_id'    => $vacancyId,
                'vacancy_title' => $vacancyId ? ($vacancyTitles[$vacancyId] ?? "Вакансія #{$vacancyId}") : '—',
                'days'          => $days,
                'amount_uah'    => $amountUah,
                'processed_at'  => \Carbon\Carbon::parse($row->processed_at),
            ];
        });

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $rawPage->total(),
            $rawPage->perPage(),
            $rawPage->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    #[Computed]
    public function stats(): array
    {
        $vacancyIds = $this->vacancyIds();

        if (empty($vacancyIds)) {
            return ['total_count' => 0, 'total_uah' => 0.0];
        }

        $orderIds = DB::table('payment_processed_events')
            ->where(function ($q) use ($vacancyIds) {
                foreach ($vacancyIds as $vid) {
                    $q->orWhere('order_id', 'LIKE', "vac_{$vid}_%");
                }
            })
            ->pluck('order_id');

        $totalUah = $orderIds->sum(function (string $orderId): float {
            [, $days] = CheckoutService::parseOrderId($orderId);
            return $days ? (config("payments.prices.{$days}") ?? 0) / 100 : 0;
        });

        return [
            'total_count' => $orderIds->count(),
            'total_uah'   => $totalUah,
        ];
    }
}
?>

<div>
    <x-employer-tabs />

    <div class="max-w-4xl mx-auto px-4 py-8">

        <h1 class="text-2xl font-bold text-gray-900 mb-2">Мої платежі</h1>
        <p class="text-gray-500 text-sm mb-8">Історія всіх оплат за публікації вакансій</p>

        {{-- Статистика --}}
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="rounded-lg bg-white border border-gray-200 p-5">
                <p class="text-sm text-gray-500">Усього транзакцій</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->stats['total_count'] }}</p>
            </div>
            <div class="rounded-lg bg-white border border-gray-200 p-5">
                <p class="text-sm text-gray-500">Загальна сума</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">
                    {{ number_format($this->stats['total_uah'], 0, '.', ' ') }} ₴
                </p>
            </div>
        </div>

        {{-- Таблиця --}}
        @if($this->transactions->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <svg class="mx-auto w-12 h-12 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <p>Платежів ще немає</p>
                <p class="text-sm mt-1">Вони з'являться після першої оплати</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Дата</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Вакансія</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Тариф</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-600">Сума</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Провайдер</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($this->transactions as $tx)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                    {{ $tx->processed_at->locale('uk')->isoFormat('D MMM YYYY, HH:mm') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($tx->vacancy_id)
                                        <a href="{{ route('employer.vacancies.edit', $tx->vacancy_id) }}"
                                           class="text-blue-600 hover:underline">
                                            {{ Str::limit($tx->vacancy_title, 35) }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $tx->days ? $tx->days . ' днів' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">
                                    {{ $tx->amount_uah ? number_format($tx->amount_uah, 0, '.', ' ') . ' ₴' : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tx->gateway_class }}">
                                        {{ $tx->gateway_label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($this->transactions->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $this->transactions->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
