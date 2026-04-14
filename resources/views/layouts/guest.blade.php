<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'My Job') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/webp" href="{{ asset('img/logo/mj-logo-200x200.webp') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            (function() {
                const saved = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', saved);
                if (saved === 'dark') document.documentElement.classList.add('dark');
            })();
        </script>
    </head>
    <body class="font-sans antialiased guest-body" style="min-height: 100vh; display: flex; flex-direction: column; background-color: #f0f2f5;">

        <x-header />

        <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 140px 16px 48px;">
            <div style="width: 100%; max-width: 480px;">
                {{ $slot }}
            </div>
        </main>

        <x-footer />

    </body>
</html>
