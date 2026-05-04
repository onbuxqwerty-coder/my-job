<?php

declare(strict_types=1);

use App\Enums\PlanFeature;
use App\Enums\PlanType;
use App\Models\EmployerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Vacancy;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public function activatePlan(int $planId): void
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        if ($plan->type !== PlanType::Free && $plan->price_monthly > 0) {
            $this->redirect(route('employer.billing.checkout', $plan), navigate: false);
            return;
        }

        app(SubscriptionService::class)->activate(auth()->user(), $plan);

        unset($this->currentSubscription, $this->subscriptionHistory);
    }

    #[Computed]
    public function currentSubscription(): ?EmployerSubscription
    {
        return EmployerSubscription::with('plan')
            ->where('user_id', auth()->id())
            ->active()
            ->latest()
            ->first();
    }

    #[Computed]
    public function plans(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::where('is_active', true)->get();
    }

    #[Computed]
    public function subscriptionHistory(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return EmployerSubscription::with('plan')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10, pageName: 'subPage');
    }

    #[Computed]
    public function activeJobsCount(): int
    {
        return app(SubscriptionService::class)->activeJobsCount(auth()->user());
    }

    #[Computed]
    public function remainingHot(): int
    {
        return app(SubscriptionService::class)->getRemainingHot(auth()->user());
    }

    #[Computed]
    public function paymentTransactions(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $companyId = auth()->user()->company?->id;
        $vacancyIds = $companyId
            ? Vacancy::where('company_id', $companyId)->pluck('id')->toArray()
            : [];

        if (empty($vacancyIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        return DB::table('payment_processed_events')
            ->where(function ($q) use ($vacancyIds) {
                foreach ($vacancyIds as $vid) {
                    $q->orWhere('order_id', 'LIKE', "vac_{$vid}_%");
                }
            })
            ->orderByDesc('processed_at')
            ->paginate(10, pageName: 'txPage');
    }
}
?>

<div class="min-h-screen" style="background-image:url('/img/bg-main.webp?v=3');background-size:auto;background-repeat:repeat;background-attachment:fixed;">
    <x-employer-tabs />

    <div class="max-w-5xl mx-auto px-4 py-8 space-y-8">

        {{-- ── Секція 1: Поточний тариф ── --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Поточний тариф</h2>
                    @if($this->currentSubscription)
                        @php $sub = $this->currentSubscription; $plan = $sub->plan; @endphp
                        <p class="mt-1 text-2xl font-bold text-blue-600">{{ $plan->name }}</p>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Діє до {{ $sub->ends_at->locale('uk')->isoFormat('D MMMM YYYY') }}
                            @if($sub->ends_at->isPast())
                                <span class="text-red-500 font-medium">· Прострочено</span>
                            @else
                                <span class="text-green-500 font-medium">· Активний</span>
                            @endif
                        </p>

                        {{-- Лічильники --}}
                        <div class="flex flex-wrap gap-4 mt-4">
                            @php $jobLimit = $plan->feature(\App\Enums\PlanFeature::ActiveJobs); @endphp
                            <div class="bg-gray-50 rounded-xl px-4 py-2 text-sm">
                                <span class="font-semibold text-gray-800">{{ $this->activeJobsCount }}</span>
                                <span class="text-gray-500"> / {{ $jobLimit === 0 ? '∞' : $jobLimit }} вакансій</span>
                            </div>
                            @if((int)$plan->feature(\App\Enums\PlanFeature::HotPerMonth) > 0)
                                <div class="bg-orange-50 rounded-xl px-4 py-2 text-sm">
                                    <span class="font-semibold text-orange-700">{{ $this->remainingHot }}</span>
                                    <span class="text-orange-500"> HOT залишилось</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="mt-1 text-gray-500 text-sm">Активної підписки немає. Оберіть тариф нижче.</p>
                    @endif
                </div>

            </div>
        </div>

        {{-- ── Секція 2: Вибір тарифу ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->plans as $plan)
                    @php $isCurrent = $this->currentSubscription?->plan_id === $plan->id; @endphp
                    <div class="bg-white rounded-2xl border-2 p-5 flex flex-col
                        {{ $isCurrent ? 'border-blue-500 shadow-md' : 'border-gray-200' }}">

                        <p class="text-lg font-bold text-gray-900">{{ $plan->name }}</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1">
                            {{ $plan->price_monthly > 0 ? number_format($plan->price_monthly, 0, '.', ' ') . ' ₴/міс' : 'Безкоштовно' }}
                        </p>

                        <ul class="mt-3 space-y-1.5 text-xs text-gray-600 flex-1">
                            @php
                                $featureLabels = [
                                    'active_jobs'            => ['label' => 'Вакансій', 'type' => 'int'],
                                    'applications_per_month' => ['label' => 'Заявок/міс', 'type' => 'int'],
                                    'analytics'              => ['label' => 'Аналітика', 'type' => 'bool'],
                                    'message_templates'      => ['label' => 'Шаблони листів', 'type' => 'bool'],
                                ];
                                $hotCount = (int)($plan->features['hot_per_month'] ?? 0);
                                $topCount = (int)($plan->features['top_per_month'] ?? 0);
                                $hotDays  = (int)($plan->features['hot_days'] ?? 0);
                                $topDays  = (int)($plan->features['top_days'] ?? 0);
                            @endphp
                            @foreach($featureLabels as $key => $meta)
                                @php $val = $plan->features[$key] ?? false; @endphp
                                <li class="flex justify-between">
                                    <span>{{ $meta['label'] }}</span>
                                    @if($meta['type'] === 'bool')
                                        <span class="{{ $val ? 'text-green-600' : 'text-gray-300' }}">{{ $val ? '✓' : '—' }}</span>
                                    @else
                                        <span class="font-medium">{{ $val === 0 ? '∞' : $val }}</span>
                                    @endif
                                </li>
                            @endforeach
                            <li class="flex justify-between">
                                <span>HOT</span>
                                @if($hotCount === 0)
                                    <span class="text-gray-300">—</span>
                                @else
                                    <span class="font-medium">{{ $hotCount }}/міс · {{ $hotDays === 0 ? '∞' : $hotDays . ' д' }}</span>
                                @endif
                            </li>
                            <li class="flex justify-between">
                                <span>TOP</span>
                                @if($topCount === 0)
                                    <span class="text-gray-300">—</span>
                                @else
                                    <span class="font-medium">{{ $topCount }}/міс · {{ $topDays === 0 ? '∞' : $topDays . ' д' }}</span>
                                @endif
                            </li>
                        </ul>

                        <button wire:click="activatePlan({{ $plan->id }})"
                                @disabled($isCurrent)
                                class="mt-4 w-full py-2 text-sm font-medium rounded-xl transition-colors
                                    {{ $isCurrent
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                            {{ $isCurrent ? 'Активний' : 'Активувати' }}
                        </button>
                    </div>
                @endforeach
        </div>

        {{-- ── Секція 3: Історія підписок ── --}}
        @if($this->subscriptionHistory->isNotEmpty())
            <div>
                <h3 class="text-base font-semibold text-gray-900 mb-3">Історія підписок</h3>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background-color:#D2D2D2;" class="border-b border-gray-300">
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Тариф</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Початок</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Закінчення</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Статус</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($this->subscriptionHistory as $sub)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $sub->plan->name }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $sub->starts_at->format('d.m.Y') }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $sub->ends_at->format('d.m.Y') }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badge = match($sub->status) {
                                                'active'    => 'bg-green-100 text-green-700',
                                                'cancelled' => 'bg-gray-100 text-gray-500',
                                                'expired'   => 'bg-red-100 text-red-600',
                                                default     => 'bg-gray-100 text-gray-500',
                                            };
                                            $label = match($sub->status) {
                                                'active'    => 'Активний',
                                                'cancelled' => 'Скасовано',
                                                'expired'   => 'Прострочено',
                                                default     => $sub->status,
                                            };
                                        @endphp
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($this->subscriptionHistory->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">
                            {{ $this->subscriptionHistory->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ── Секція 4: Історія платежів ── --}}
        @if($this->paymentTransactions->isNotEmpty())
            <div>
                <h3 class="text-base font-semibold text-gray-900 mb-3">Історія платежів</h3>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background-color:#D2D2D2;" class="border-b border-gray-300">
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Дата</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Order ID</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Провайдер</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($this->paymentTransactions as $tx)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ \Carbon\Carbon::parse($tx->processed_at)->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ $tx->order_id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                            {{ ucfirst($tx->gateway) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($this->paymentTransactions->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">
                            {{ $this->paymentTransactions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>
