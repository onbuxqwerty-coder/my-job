<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'My Job') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/x-icon" href="{{ asset('img/logo/favicon.ico') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" style="background-color: #f0f2f5; min-height: 100vh; display: flex; flex-direction: column;">

        <x-header />

        <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 140px 16px 48px;">
            <div style="width: 100%; max-width: 480px;">
                {{ $slot }}
            </div>
        </main>

        <x-footer />

    </body>
</html>
