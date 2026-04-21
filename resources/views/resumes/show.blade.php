<x-app-layout>
    @php
        $info     = $resume->personal_info ?? [];
        $location = $resume->location ?? [];
        $name     = trim(($info['first_name'] ?? '') . ' ' . ($info['last_name'] ?? ''));
        $position = $info['position'] ?? null;
        $email    = $info['email'] ?? null;
        $phone    = $info['phone'] ?? null;
        $about    = $info['about'] ?? null;
        $city     = $location['city'] ?? null;
        $remote   = $location['remote'] ?? false;
    @endphp

    <div class="max-w-3xl mx-auto px-4 py-10 sm:px-6">

        {{-- Header --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-8 py-8 mb-6">
            <div class="flex items-start gap-6">
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-2xl shrink-0">
                    {{ mb_strtoupper(mb_substr($name ?: '?', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $name ?: 'Резюме' }}</h1>
                    @if ($position)
                        <p class="text-base text-gray-600 mt-1">{{ $position }}</p>
                    @endif
                    <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-500">
                        @if ($city)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $city }}
                            </span>
                        @endif
                        @if ($remote)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Дистанційно
                            </span>
                        @endif
                        @if ($email)
                            <span>{{ $email }}</span>
                        @endif
                        @if ($phone)
                            <span>{{ $phone }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @if ($about)
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Про себе</h2>
                    <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">{{ $about }}</p>
                </div>
            @endif
        </div>

        {{-- Experience --}}
        @if ($resume->experiences->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-8 py-6 mb-6">
                <h2 class="text-base font-bold text-gray-900 mb-4">Досвід роботи</h2>
                <div class="space-y-5">
                    @foreach ($resume->experiences()->orderByDesc('start_date')->get() as $exp)
                        <div class="flex gap-4">
                            <div class="w-2 h-2 rounded-full bg-blue-500 mt-2 shrink-0"></div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">{{ $exp->position }}</p>
                                <p class="text-sm text-gray-600">{{ $exp->company_name }}
                                    @if ($exp->company_industry)
                                        · {{ $exp->company_industry }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ \Carbon\Carbon::parse($exp->start_date)->format('m.Y') }} —
                                    {{ $exp->is_current ? 'по теперішній час' : ($exp->end_date ? \Carbon\Carbon::parse($exp->end_date)->format('m.Y') : '') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Skills --}}
        @if ($resume->skills->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-8 py-6">
                <h2 class="text-base font-bold text-gray-900 mb-4">Навички</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($resume->skills()->orderBy('skill_name')->get() as $skill)
                        <span class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-lg">
                            {{ $skill->skill_name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
