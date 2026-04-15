@php
    $currentRoute = Route::currentRouteName();

    $tabs = [
        ['route' => 'seeker.dashboard',    'label' => 'Огляд'],
        ['route' => 'seeker.applications', 'label' => 'Мої заявки'],
        ['route' => 'seeker.interviews',   'label' => 'Співбесіди'],
        ['route' => 'seeker.profile',      'label' => 'Мій профіль'],
    ];

    $activeMap = [
        'seeker.application.detail' => 'seeker.applications',
    ];

    $activeTab = $activeMap[$currentRoute] ?? $currentRoute;

    $user            = auth()->user();
    $totalApps       = $user->applications()->count();
    $activeApps      = $user->applications()->whereIn('status', ['pending', 'screening', 'interview'])->count();
    $upcomingIntvs   = \App\Models\Interview::whereHas('application', fn($q) => $q->where('user_id', $user->id))
                        ->where('scheduled_at', '>=', now())
                        ->whereIn('status', [\App\Enums\InterviewStatus::Scheduled->value, \App\Enums\InterviewStatus::Confirmed->value])
                        ->count();
@endphp

<div class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header row --}}
        <div style="display:flex; align-items:center; justify-content:space-between; padding-top:20px; padding-bottom:12px; gap:16px;">

            {{-- Left: user name --}}
            <div style="flex-shrink:0;">
                <p style="font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.08em; margin-bottom:2px;">Кабінет шукача</p>
                <h1 class="seeker-header-title" style="font-size:1.2rem; font-weight:800; color:#111827; line-height:1.3; margin:0;">
                    {{ $user->name }}
                </h1>
            </div>

            {{-- Center: stats --}}
            <div style="display:flex; align-items:center; gap:20px;">
                <div style="display:flex; align-items:baseline; gap:6px;">
                    <span style="font-size:1.5rem; font-weight:800; color:#111827;">{{ $totalApps }}</span>
                    <span style="font-size:0.75rem; color:#9ca3af; white-space:nowrap;">всього заявок</span>
                </div>
                <div style="width:1px; height:20px; background:#e5e7eb;"></div>
                <div style="display:flex; align-items:baseline; gap:6px;">
                    <span style="font-size:1.5rem; font-weight:800; color:#2563eb;">{{ $activeApps }}</span>
                    <span style="font-size:0.75rem; color:#9ca3af; white-space:nowrap;">активних</span>
                </div>
                <div style="width:1px; height:20px; background:#e5e7eb;"></div>
                <div style="display:flex; align-items:baseline; gap:6px;">
                    <span style="font-size:1.5rem; font-weight:800; color:#7c3aed;">{{ $upcomingIntvs }}</span>
                    <span style="font-size:0.75rem; color:#9ca3af; white-space:nowrap;">співбесід</span>
                </div>
            </div>

            {{-- Right: CTA --}}
            <a href="{{ route('home') }}"
               style="flex-shrink:0; display:inline-flex; align-items:center; gap:6px; padding:8px 18px; font-size:0.875rem; font-weight:700; color:#fff; background:#2563eb; border-radius:12px; text-decoration:none; white-space:nowrap; box-shadow:0 1px 4px rgba(37,99,235,.3);">
                <svg style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Знайти вакансії
            </a>
        </div>

        {{-- Tabs --}}
        <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Seeker tabs">
            @foreach($tabs as $tab)
                @php $isActive = $activeTab === $tab['route']; @endphp
                <a href="{{ route($tab['route']) }}"
                   class="shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
                          {{ $isActive
                              ? 'border-blue-600 text-blue-600'
                              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</div>
