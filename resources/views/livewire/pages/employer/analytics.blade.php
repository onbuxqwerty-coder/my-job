<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url]
    public string $period = '30d';

    public function updatedPeriod(): void
    {
        // clear cache on period change
        Cache::forget($this->cacheKey());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function companyId(): int
    {
        return auth()->user()->company->id;
    }

    private function dateRange(): array
    {
        $now = Carbon::now();

        $from = match($this->period) {
            '7d'         => $now->copy()->subDays(7)->startOfDay(),
            '30d'        => $now->copy()->subDays(30)->startOfDay(),
            '90d'        => $now->copy()->subDays(90)->startOfDay(),
            'this_month' => $now->copy()->startOfMonth(),
            'this_year'  => $now->copy()->startOfYear(),
            default      => $now->copy()->subDays(30)->startOfDay(),
        };

        return [$from, $now->endOfDay()];
    }

    private function cacheKey(): string
    {
        return 'analytics.' . $this->companyId() . '.' . $this->period;
    }

    private function loadStats(): array
    {
        return Cache::remember($this->cacheKey(), 300, function () {
            [$from, $to] = $this->dateRange();
            $companyId   = $this->companyId();

            // Base query scoped to company
            $appQuery = fn () => Application::whereHas(
                'vacancy',
                fn ($q) => $q->where('company_id', $companyId)
            );

            // ── KPI ───────────────────────────────────────────────────────────

            $activeVacancies = Vacancy::where('company_id', $companyId)
                ->where('is_active', true)->count();

            $totalApplications = $appQuery()->whereBetween('created_at', [$from, $to])->count();

            $hiredCount = $appQuery()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', ApplicationStatus::Hired->value)
                ->count();

            $conversionRate = $totalApplications > 0
                ? round($hiredCount / $totalApplications * 100, 1)
                : 0;

            $avgRating = $appQuery()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('rating')
                ->avg('rating');

            // ── Funnel ────────────────────────────────────────────────────────

            $funnelRaw = $appQuery()
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $funnel = [];
            $maxFunnel = max(array_values($funnelRaw) ?: [1]);
            foreach (ApplicationStatus::cases() as $status) {
                $count = $funnelRaw[$status->value] ?? 0;
                $funnel[] = [
                    'status'  => $status->value,
                    'label'   => $status->label(),
                    'color'   => $status->color(),
                    'count'   => $count,
                    'percent' => $maxFunnel > 0 ? round($count / $maxFunnel * 100) : 0,
                ];
            }

            // ── Trend (applications per day) ──────────────────────────────────

            $trendRaw = $appQuery()
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('DATE(created_at) as day, count(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->toArray();

            // Fill missing days
            $trend = [];
            $current = $from->copy();
            while ($current <= $to) {
                $key = $current->format('Y-m-d');
                $trend[] = [
                    'label' => $current->format('d.m'),
                    'count' => $trendRaw[$key] ?? 0,
                ];
                $current->addDay();
            }

            $trendMax = max(array_column($trend, 'count') ?: [1]);

            // ── Top 5 vacancies ───────────────────────────────────────────────

            $topVacancies = Vacancy::where('company_id', $companyId)
                ->withCount(['applications as app_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
                ->having('app_count', '>', 0)
                ->orderByDesc('app_count')
                ->limit(5)
                ->get(['id', 'title'])
                ->map(fn ($v) => ['id' => $v->id, 'title' => $v->title, 'app_count' => $v->app_count])
                ->toArray();

            // ── Team activity ─────────────────────────────────────────────────

            $vacancyIds = Vacancy::where('company_id', $companyId)->pluck('id');
            $appIds     = Application::whereIn('vacancy_id', $vacancyIds)->pluck('id');

            $msgActivity = CandidateMessage::whereIn('application_id', $appIds)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('sender_id, count(*) as total')
                ->groupBy('sender_id')
                ->with('sender:id,name')
                ->get()
                ->keyBy('sender_id');

            $noteActivity = ApplicationNote::whereIn('application_id', $appIds)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('author_id, count(*) as total')
                ->groupBy('author_id')
                ->with('author:id,name')
                ->get()
                ->keyBy('author_id');

            $ivActivity = Interview::whereIn('application_id', $appIds)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('created_by, count(*) as total')
                ->groupBy('created_by')
                ->with('creator:id,name')
                ->get()
                ->keyBy('created_by');

            // Merge all user IDs
            $userIds = collect()
                ->merge($msgActivity->keys())
                ->merge($noteActivity->keys())
                ->merge($ivActivity->keys())
                ->unique();

            $teamActivity = $userIds->map(function ($userId) use ($msgActivity, $noteActivity, $ivActivity) {
                $name = $msgActivity[$userId]?->sender?->name
                    ?? $noteActivity[$userId]?->author?->name
                    ?? $ivActivity[$userId]?->creator?->name
                    ?? 'Невідомий';

                $msgs  = $msgActivity[$userId]?->total ?? 0;
                $notes = $noteActivity[$userId]?->total ?? 0;
                $ivs   = $ivActivity[$userId]?->total ?? 0;

                return [
                    'name'       => $name,
                    'messages'   => $msgs,
                    'notes'      => $notes,
                    'interviews' => $ivs,
                    'total'      => $msgs + $notes + $ivs,
                ];
            })->sortByDesc('total')->values()->toArray();

            return compact(
                'activeVacancies', 'totalApplications', 'hiredCount',
                'conversionRate', 'avgRating',
                'funnel', 'trend', 'trendMax',
                'topVacancies', 'teamActivity'
            );
        });
    }

    #[Computed]
    public function stats(): array
    {
        return $this->loadStats();
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
    <x-employer-tabs />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Sub-header --}}
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-lg font-semibold text-gray-900">Аналітика</h2>

            {{-- Period selector --}}
            <div class="flex gap-1 bg-white border border-gray-200 rounded-xl p-1">
                @foreach([
                    '7d'         => '7 днів',
                    '30d'        => '30 днів',
                    '90d'        => '90 днів',
                    'this_month' => 'Місяць',
                    'this_year'  => 'Рік',
                ] as $key => $label)
                    <button wire:click="$set('period', '{{ $key }}')"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors
                                   {{ $period === $key ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        @php $s = $this->stats; @endphp

        {{-- ===== KPI CARDS ===== --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border employer-card-border p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Активні вакансії</p>
                <p class="text-3xl font-bold text-gray-900">{{ $s['activeVacancies'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border employer-card-border p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Кандидатів</p>
                <p class="text-3xl font-bold text-blue-600">{{ $s['totalApplications'] }}</p>
                <p class="text-xs text-gray-400 mt-1">за обраний період</p>
            </div>
            <div class="bg-white rounded-2xl border employer-card-border p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Конвертація</p>
                <p class="text-3xl font-bold text-green-600">{{ $s['conversionRate'] }}%</p>
                <p class="text-xs text-gray-400 mt-1">{{ $s['hiredCount'] }} найнято</p>
            </div>
            <div class="bg-white rounded-2xl border employer-card-border p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Сер. рейтинг</p>
                <p class="text-3xl font-bold text-amber-500">
                    {{ $s['avgRating'] ? number_format($s['avgRating'], 1) : '—' }}
                </p>
                <p class="text-xs text-gray-400 mt-1">з 5.0</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- ===== CONVERSION FUNNEL ===== --}}
            <div class="bg-white rounded-2xl border employer-card-border p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-5">Воронка конвертації</h2>

                @php
                    $funnelColorMap = [
                        'gray'   => ['bar' => 'bg-gray-300',   'text' => 'text-gray-500',  'badge' => 'bg-gray-100 text-gray-600'],
                        'blue'   => ['bar' => 'bg-blue-400',   'text' => 'text-blue-600',  'badge' => 'bg-blue-100 text-blue-700'],
                        'yellow' => ['bar' => 'bg-yellow-400', 'text' => 'text-yellow-600','badge' => 'bg-yellow-100 text-yellow-700'],
                        'green'  => ['bar' => 'bg-green-500',  'text' => 'text-green-600', 'badge' => 'bg-green-100 text-green-700'],
                        'red'    => ['bar' => 'bg-red-400',    'text' => 'text-red-500',   'badge' => 'bg-red-100 text-red-600'],
                    ];
                @endphp

                <div class="space-y-4">
                    @foreach($s['funnel'] as $row)
                        @php
                            $colors = $funnelColorMap[$row['color']] ?? $funnelColorMap['gray'];
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors['badge'] }}">
                                    {{ $row['label'] }}
                                </span>
                                <span class="text-sm font-bold text-gray-800">{{ $row['count'] }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="{{ $colors['bar'] }} h-2 rounded-full transition-all duration-500"
                                     style="width: {{ max($row['percent'], $row['count'] > 0 ? 2 : 0) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($s['totalApplications'] > 0)
                    <p class="text-xs text-gray-400 mt-4 pt-4 border-t border-gray-100">
                        Загальна конвертація: <strong>{{ $s['conversionRate'] }}%</strong>
                        ({{ $s['hiredCount'] }} з {{ $s['totalApplications'] }})
                    </p>
                @endif
            </div>

            {{-- ===== TREND CHART ===== --}}
            <div class="bg-white rounded-2xl border employer-card-border p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-5">Тренд подачі заявок</h2>

                @php
                    $trend    = $s['trend'];
                    $trendMax = max($s['trendMax'], 1);
                    $barCount = count($trend);
                    // Limit to last 30 bars for readability
                    if ($barCount > 30) {
                        $trend    = array_slice($trend, -30);
                        $barCount = 30;
                    }
                @endphp

                @if(array_sum(array_column($trend, 'count')) === 0)
                    <div class="flex items-center justify-center h-40 text-sm text-gray-400">
                        Даних за обраний період немає.
                    </div>
                @else
                    {{-- SVG bar chart --}}
                    <div class="overflow-x-auto">
                        <svg viewBox="0 0 {{ $barCount * 14 }} 80" class="w-full" style="min-width: {{ max($barCount * 14, 200) }}px; height: 120px;">
                            @foreach($trend as $i => $day)
                                @php
                                    $barH  = $trendMax > 0 ? round($day['count'] / $trendMax * 60) : 0;
                                    $x     = $i * 14 + 2;
                                    $y     = 62 - $barH;
                                @endphp
                                <rect x="{{ $x }}" y="{{ $y }}" width="10" height="{{ $barH }}"
                                      rx="2" fill="{{ $day['count'] > 0 ? '#3b82f6' : '#e5e7eb' }}"
                                      opacity="{{ $day['count'] > 0 ? '0.85' : '1' }}">
                                    <title>{{ $day['label'] }}: {{ $day['count'] }}</title>
                                </rect>
                            @endforeach
                            {{-- X-axis line --}}
                            <line x1="0" y1="63" x2="{{ $barCount * 14 }}" y2="63" stroke="#e5e7eb" stroke-width="1"/>
                        </svg>
                    </div>

                    {{-- X-axis labels (show every Nth) --}}
                    @php
                        $step = max(1, (int) ceil($barCount / 7));
                        $labelItems = array_filter($trend, fn ($_, $i) => $i % $step === 0, ARRAY_FILTER_USE_BOTH);
                    @endphp
                    <div class="flex justify-between mt-1 px-0.5">
                        @foreach($labelItems as $day)
                            <span class="text-xs text-gray-400">{{ $day['label'] }}</span>
                        @endforeach
                    </div>

                    <p class="text-xs text-gray-400 mt-3 pt-3 border-t border-gray-100">
                        Всього: <strong>{{ array_sum(array_column($trend, 'count')) }}</strong> заявок за період
                    </p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- ===== TOP-5 VACANCIES ===== --}}
            <div class="bg-white rounded-2xl border employer-card-border p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Топ-5 вакансій</h2>

                @if(empty($s['topVacancies']))
                    <p class="text-sm text-gray-400">Немає заявок за обраний період.</p>
                @else
                    @php $topMax = $s['topVacancies'][0]['app_count'] ?? 1; @endphp
                    <div class="space-y-3">
                        @foreach($s['topVacancies'] as $i => $vacancy)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <a href="{{ route('employer.applicants', $vacancy['id']) }}"
                                       class="text-sm font-medium text-gray-800 hover:text-blue-600 truncate max-w-xs">
                                        {{ $i + 1 }}. {{ $vacancy['title'] }}
                                    </a>
                                    <span class="text-sm font-bold text-gray-700 shrink-0 ml-2">{{ $vacancy['app_count'] }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-blue-500 h-1.5 rounded-full"
                                         style="width: {{ $topMax > 0 ? round($vacancy['app_count'] / $topMax * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ===== TEAM ACTIVITY ===== --}}
            <div class="bg-white rounded-2xl border employer-card-border p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Активність команди</h2>

                @if(empty($s['teamActivity']))
                    <p class="text-sm text-gray-400">Активності за обраний період немає.</p>
                @else
                    <div class="space-y-3">
                        @foreach($s['teamActivity'] as $member)
                            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700 shrink-0">
                                    {{ mb_strtoupper(mb_substr($member['name'], 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">{{ $member['name'] }}</p>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                                        @if($member['messages'] > 0)
                                            <span class="text-xs text-gray-500">
                                                <span class="font-medium text-gray-700">{{ $member['messages'] }}</span> повід.
                                            </span>
                                        @endif
                                        @if($member['notes'] > 0)
                                            <span class="text-xs text-gray-500">
                                                <span class="font-medium text-gray-700">{{ $member['notes'] }}</span> нотат.
                                            </span>
                                        @endif
                                        @if($member['interviews'] > 0)
                                            <span class="text-xs text-gray-500">
                                                <span class="font-medium text-gray-700">{{ $member['interviews'] }}</span> співбесід
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="text-sm font-bold text-gray-700">{{ $member['total'] }}</span>
                                    <p class="text-xs text-gray-400">дій</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
