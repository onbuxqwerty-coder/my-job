<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'My Job') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <link rel="icon" type="image/webp" href="{{ asset('img/logo/favicon.ico') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @media (max-width: 767px) {
                #main-content { padding-top: 64px !important; }
            }
        </style>
        <script>
            (function() {
                const saved = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', saved);
                if (saved === 'dark') document.documentElement.classList.add('dark');
            })();
        </script>
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900">

        <x-header />

        <main class="min-h-screen" style="padding-top: 120px;" id="main-content">
            {{ $slot }}
        </main>

        <x-footer />

        <livewire:employer.quick-publish-form />

        @if(session('vacancy_published_id'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none;"
        >
            <div class="absolute inset-0 bg-black/60" @click="show = false"></div>
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center"
                style="display:none;"
            >
                <div class="text-5xl mb-4">🚀</div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    Вакансія вже опублікована
                </h2>
                <p class="text-gray-500 mb-1">Ми почали шукати кандидатів</p>

                <div class="my-6 h-px bg-gray-100"></div>

                <p class="text-gray-700 font-medium mb-1">
                    Хочете отримати більше кандидатів?
                </p>
                <p class="text-sm text-gray-400 mb-6">
                    Додайте ще трохи інформації (це займе 1–2 хв)
                </p>

                <div class="flex flex-col gap-3">
                    <a
                        href="{{ route('employer.vacancies.edit', ['vacancyId' => session('vacancy_published_id')]) }}"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition text-center"
                    >
                        Покращити вакансію
                    </a>
                    <a
                        href="{{ route('employer.dashboard') }}"
                        class="w-full py-3 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-xl transition text-center"
                    >
                        Пропустити
                    </a>
                </div>
            </div>
        </div>
        @endif

    </body>
</html>
