@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="bg-white rounded-2xl border border-gray-200 p-10 max-w-md w-full text-center shadow-sm">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900 mb-2">Оплата успішна</h1>
        <p class="text-sm text-gray-500 mb-6">
            Ваш тариф буде активовано протягом кількох секунд після підтвердження платежу.
        </p>
        <a href="{{ route('employer.billing') }}"
           class="inline-block px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition-colors">
            Перейти до білінгу
        </a>
    </div>
</div>
@endsection
