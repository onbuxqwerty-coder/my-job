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

<div class="min-h-screen dark:bg-[#111827]" style="background-image:url('/img/bg-main.webp?v=3');background-size:auto;background-repeat:repeat;background-attachment:fixed;">
    <x-employer-tabs />

    <div class="max-w-5xl mx-auto px-4 py-8 space-y-8">

        {{-- ── Секція 1: Поточний тариф ── --}}
        <div class="rounded-2xl p-6" style="background-color:#1F2937;border:1px solid #4B5563">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-white">Поточний тариф</h2>
                    @if($this->currentSubscription)
                        @php $sub = $this->currentSubscription; $plan = $sub->plan; @endphp
                        <p class="mt-1 text-2xl font-bold text-blue-400">{{ $plan->name }}</p>
                        <p class="text-sm text-gray-400 mt-0.5">
                            Діє до {{ $sub->ends_at->locale('uk')->isoFormat('D MMMM YYYY') }}
                            @if($sub->ends_at->isPast())
                                <span class="text-red-400 font-medium">· Прострочено</span>
                            @else
                                <span class="text-green-400 font-medium">· Активний</span>
                            @endif
                        </p>

                        {{-- Лічильники --}}
                        <div class="flex flex-wrap gap-4 mt-4">
                            @php $jobLimit = $plan->feature(\App\Enums\PlanFeature::ActiveJobs); @endphp
                            <div class="rounded-xl px-4 py-2 text-sm" style="background-color:#111827;border:1px solid #4B5563">
                                <span class="font-semibold text-white">{{ $this->activeJobsCount }}</span>
                                <span class="text-gray-400"> / {{ $jobLimit === 0 ? '∞' : $jobLimit }} вакансій</span>
                            </div>
                            @if((int)$plan->feature(\App\Enums\PlanFeature::HotPerMonth) > 0)
                                <div class="bg-orange-500/10 rounded-xl px-4 py-2 text-sm" style="border:1px solid #4B5563">
                                    <span class="font-semibold text-orange-400">{{ $this->remainingHot }}</span>
                                    <span class="text-orange-400/70"> HOT залишилось</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="mt-1 text-gray-400 text-sm">Активної підписки немає. Оберіть тариф нижче.</p>
                    @endif
                </div>

            </div>
        </div>

        {{-- ── Секція 2: Вибір тарифу ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->plans as $plan)
                    @php $isCurrent = $this->currentSubscription?->plan_id === $plan->id; @endphp
                    <div class="rounded-2xl p-5 flex flex-col"
                         style="background-color:#1F2937;border:{{ $isCurrent ? '2px solid #3B82F6' : '1px solid #4B5563' }}">

                        <p class="text-lg font-bold text-white">{{ $plan->name }}</p>
                        <p class="text-2xl font-bold text-blue-400 mt-1">
                            {{ $plan->price_monthly > 0 ? number_format($plan->price_monthly, 0, '.', ' ') . ' ₴/міс' : 'Безкоштовно' }}
                        </p>

                        <ul class="mt-3 space-y-1.5 text-xs text-gray-400 flex-1">
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
                                        ? 'text-gray-500 cursor-not-allowed'
                                        : 'bg-blue-600 text-white hover:bg-blue-700' }}"
                                @if($isCurrent) style="background-color:#374151;border:1px solid #4B5563" @endif>
                            {{ $isCurrent ? 'Активний' : 'Активувати' }}
                        </button>
                    </div>
                @endforeach
        </div>

        {{-- ── Секція 3: Історія підписок ── --}}
        @if($this->subscriptionHistory->isNotEmpty())
            <div>
                <h3 class="text-base font-semibold text-white mb-3">Історія підписок</h3>
                <div class="rounded-xl overflow-hidden" style="background-color:#1F2937;border:1px solid #4B5563">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background-color:#111827;border-bottom:1px solid #4B5563">
                                <th class="text-left px-4 py-3 font-medium text-gray-400">Тариф</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-400">Початок</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-400">Закінчення</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-400">Статус</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($this->subscriptionHistory as $sub)
                                <tr class="hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-white">{{ $sub->plan->name }}</td>
                                    <td class="px-4 py-3 text-gray-400">{{ $sub->starts_at->format('d.m.Y') }}</td>
                                    <td class="px-4 py-3 text-gray-400">{{ $sub->ends_at->format('d.m.Y') }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badge = match($sub->status) {
                                                'active'    => 'bg-green-500/20 text-green-400',
                                                'cancelled' => 'bg-gray-500/20 text-gray-400',
                                                'expired'   => 'bg-red-500/20 text-red-400',
                                                default     => 'bg-gray-500/20 text-gray-400',
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
                        <div class="px-4 py-3" style="border-top:1px solid #4B5563">
                            {{ $this->subscriptionHistory->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ── Секція 4: Історія платежів ── --}}
        @if($this->paymentTransactions->isNotEmpty())
            <div>
                <h3 class="text-base font-semibold text-white mb-3">Історія платежів</h3>
                <div class="rounded-xl overflow-hidden" style="background-color:#1F2937;border:1px solid #4B5563">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background-color:#111827;border-bottom:1px solid #4B5563">
                                <th class="text-left px-4 py-3 font-medium text-gray-400">Дата</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-400">Order ID</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-400">Провайдер</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($this->paymentTransactions as $tx)
                                <tr class="hover:bg-white/5">
                                    <td class="px-4 py-3 text-gray-400">
                                        {{ \Carbon\Carbon::parse($tx->processed_at)->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-300 font-mono text-xs">{{ $tx->order_id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-500/20 text-blue-400">
                                            {{ ucfirst($tx->gateway) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($this->paymentTransactions->hasPages())
                        <div class="px-4 py-3" style="border-top:1px solid #4B5563">
                            {{ $this->paymentTransactions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>
