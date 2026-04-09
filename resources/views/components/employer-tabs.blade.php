@php
    $currentRoute = Route::currentRouteName();

    $tabs = [
        ['route' => 'employer.dashboard',         'label' => 'Вакансії'],
        ['route' => 'employer.candidates',         'label' => 'Кандидати'],
        ['route' => 'employer.message.templates',  'label' => 'Шаблони повідомлень'],
        ['route' => 'employer.analytics',          'label' => 'Аналітика'],
        ['route' => 'employer.profile',            'label' => 'Профіль компанії'],
    ];

    $activeMap = [
        'employer.vacancies.create' => 'employer.dashboard',
        'employer.vacancies.edit'   => 'employer.dashboard',
        'employer.applicants'       => 'employer.dashboard',
        'employer.candidate.detail' => 'employer.candidates',
    ];

    $activeTab = $activeMap[$currentRoute] ?? $currentRoute;

    $company = auth()->user()?->company;

    $totalVacancies    = $company ? $company->vacancies()->count() : 0;
    $activeVacancies   = $company ? $company->vacancies()->where('is_active', true)->count() : 0;
    $totalApplications = $company
        ? \App\Models\Application::whereHas('vacancy', fn ($q) => $q->where('company_id', $company->id))->count()
        : 0;
@endphp

<div class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Single row: company name | stats | CTA --}}
        <div style="display:flex; align-items:center; justify-content:space-between; padding-top:20px; padding-bottom:12px; gap:16px;">

            {{-- Left: company name --}}
            <div style="flex-shrink:0;">
                <p style="font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.08em; margin-bottom:2px;">Кабінет роботодавця</p>
                <h1 style="font-size:1.2rem; font-weight:800; color:#111827; line-height:1.3; margin:0;">
                    {{ $company?->name ?? 'Моя компанія' }}
                </h1>
            </div>

            {{-- Center: stats --}}
            @if($company)
                <div style="display:flex; align-items:center; gap:20px;">
                    <div style="display:flex; align-items:baseline; gap:6px;">
                        <span style="font-size:1.5rem; font-weight:800; color:#111827;">{{ $totalVacancies }}</span>
                        <span style="font-size:0.75rem; color:#9ca3af; white-space:nowrap;">всього вакансій</span>
                    </div>
                    <div style="width:1px; height:20px; background:#e5e7eb;"></div>
                    <div style="display:flex; align-items:baseline; gap:6px;">
                        <span style="font-size:1.5rem; font-weight:800; color:#16a34a;">{{ $activeVacancies }}</span>
                        <span style="font-size:0.75rem; color:#9ca3af; white-space:nowrap;">активних</span>
                    </div>
                    <div style="width:1px; height:20px; background:#e5e7eb;"></div>
                    <div style="display:flex; align-items:baseline; gap:6px;">
                        <span style="font-size:1.5rem; font-weight:800; color:#2563eb;">{{ $totalApplications }}</span>
                        <span style="font-size:0.75rem; color:#9ca3af; white-space:nowrap;">відгуків</span>
                    </div>
                </div>
            @endif

            {{-- Right: CTA --}}
            <a href="{{ route('employer.vacancies.create') }}"
               style="flex-shrink:0; display:inline-flex; align-items:center; gap:6px; padding:8px 18px; font-size:0.875rem; font-weight:700; color:#fff; background:#2563eb; border-radius:12px; text-decoration:none; white-space:nowrap; box-shadow:0 1px 4px rgba(37,99,235,.3);">
                <svg style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Нова вакансія
            </a>
        </div>

        {{-- Tabs --}}
        <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Employer tabs">
            @foreach($tabs as $tab)
                @php
                    $isActive = $activeTab === $tab['route'];
                @endphp
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
