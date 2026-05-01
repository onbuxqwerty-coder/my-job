<x-app-layout>
@php
    $plans = \App\Models\SubscriptionPlan::where('is_active', true)
        ->whereIn('type', [\App\Enums\PlanType::Start, \App\Enums\PlanType::Business, \App\Enums\PlanType::Pro])
        ->orderBy('price_monthly')
        ->get();
@endphp

{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-white">
    <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-indigo-500/10 blur-3xl"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
        <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
            {{-- Text content --}}
            <div class="flex-1 max-w-2xl">
                <div class="inline-flex items-center gap-2 bg-blue-500/10 border border-blue-400/20 rounded-full px-4 py-1.5 text-sm text-blue-300 font-medium mb-6">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    Для роботодавців
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight mb-6">
                    Знайдіть найкращих<br>
                    <span class="text-blue-400">спеціалістів України</span>
                </h1>
                <p class="text-lg sm:text-xl text-slate-300 mb-10 leading-relaxed">
                    MyJob — найшвидший спосіб опублікувати вакансію та отримати відгуки від кандидатів.
                    Telegram-розсилка, аналітика, шаблони — все в одному місці.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-400 text-white font-bold px-8 py-4 rounded-2xl transition-all duration-200 text-lg shadow-lg shadow-blue-500/30 hover:shadow-blue-400/40 hover:-translate-y-0.5">
                        Почати безкоштовно
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold px-8 py-4 rounded-2xl transition-all duration-200 text-lg backdrop-blur-sm">
                        Увійти в кабінет
                    </a>
                </div>
                <p class="mt-5 text-sm text-slate-400">Реєстрація займає менше хвилини. Перша вакансія — безкоштовно.</p>
            </div>

            {{-- Hero image --}}
            <div class="flex-shrink-0 w-full lg:w-auto hidden lg:block">
                <div class="relative">
                    <div class="absolute inset-0 rounded-3xl bg-blue-500/20 blur-2xl scale-105"></div>
                    <img
                        src="{{ asset('img/office_people.png') }}"
                        alt="Команда фахівців переглядає вакансії на планшеті"
                        class="relative w-80 xl:w-96 rounded-3xl shadow-2xl object-cover object-top"
                        style="max-height: 520px;"
                        loading="eager"
                    >
                </div>
            </div>
        </div>
    </div>
    {{-- Stats bar --}}
    <div class="relative border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl font-extrabold text-white">10 000+</div>
                    <div class="text-sm text-slate-400 mt-1">активних кандидатів</div>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-white">500+</div>
                    <div class="text-sm text-slate-400 mt-1">вакансій щомісяця</div>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-white">48 год</div>
                    <div class="text-sm text-slate-400 mt-1">середній час до відгуку</div>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-white">94%</div>
                    <div class="text-sm text-slate-400 mt-1">задоволених компаній</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- How it works Section --}}
<section class="bg-white py-20 lg:py-28" style="background-image: url('{{ asset('img/bg-main.webp') }}'); background-repeat: repeat;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">Як це працює?</h2>
            <p class="text-lg text-slate-500 max-w-2xl mx-auto">Від реєстрації до першого відгуку — не більше 15 хвилин</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
            <div class="relative text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 text-2xl font-extrabold mb-6">1</div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Зареєструйтесь</h3>
                <p class="text-slate-500 leading-relaxed">Створіть акаунт і заповніть профіль компанії. Без верифікацій та затримок — одразу публікуйте.</p>
            </div>
            <div class="relative text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 text-2xl font-extrabold mb-6">2</div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Опублікуйте вакансію</h3>
                <p class="text-slate-500 leading-relaxed">Зручна форма, категорії, зарплата, вимоги. Вакансія одразу потрапляє у Telegram-канал та на сайт.</p>
            </div>
            <div class="relative text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 text-2xl font-extrabold mb-6">3</div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Отримуйте кандидатів</h3>
                <p class="text-slate-500 leading-relaxed">Переглядайте відгуки, листуйтесь з кандидатами, змінюйте статуси і призначайте інтерв'ю — все в одному кабінеті.</p>
            </div>
        </div>
    </div>
</section>

{{-- Features Section --}}
<section class="py-20 lg:py-28 bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">Всі інструменти роботодавця</h2>
            <p class="text-lg text-slate-400 max-w-2xl mx-auto">Повний цикл найму — від оголошення до виходу нового співробітника</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Feature 1 --}}
            <div class="rounded-2xl p-7 hover:bg-slate-700/50 transition-colors duration-200" style="background-color: #1e293b; border: 1px solid #7A7A7A;">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-500/15 text-blue-400 mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Telegram-розсилка</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Кожна нова вакансія автоматично надходить підписникам Telegram-каналу. Миттєве охоплення аудиторії.</p>
            </div>
            {{-- Feature 2 --}}
            <div class="rounded-2xl p-7 hover:bg-slate-700/50 transition-colors duration-200" style="background-color: #1e293b; border: 1px solid #7A7A7A;">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-green-500/15 text-green-400 mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Аналітика відгуків</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Перегляди, відгуки, конверсія по кожній вакансії. Розумійте, що працює, а що — ні.</p>
            </div>
            {{-- Feature 3 --}}
            <div class="rounded-2xl p-7 hover:bg-slate-700/50 transition-colors duration-200" style="background-color: #1e293b; border: 1px solid #7A7A7A;">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-purple-500/15 text-purple-400 mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Шаблони повідомлень</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Готові шаблони для комунікації з кандидатами — запрошення на інтерв'ю, відмова, пропозиція. Економте час HR.</p>
            </div>
            {{-- Feature 4 --}}
            <div class="rounded-2xl p-7 hover:bg-slate-700/50 transition-colors duration-200" style="background-color: #1e293b; border: 1px solid #7A7A7A;">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-orange-500/15 text-orange-400 mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Управління командою</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Додавайте HR-менеджерів до свого акаунту. Розподіляйте вакансії та кандидатів між членами команди.</p>
            </div>
            {{-- Feature 5 --}}
            <div class="rounded-2xl p-7 hover:bg-slate-700/50 transition-colors duration-200" style="background-color: #1e293b; border: 1px solid #7A7A7A;">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-red-500/15 text-red-400 mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Планування інтерв'ю</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Зручний календар співбесід з нагадуваннями. Кандидати отримують автоматичні сповіщення про час і місце.</p>
            </div>
            {{-- Feature 6 --}}
            <div class="rounded-2xl p-7 hover:bg-slate-700/50 transition-colors duration-200" style="background-color: #1e293b; border: 1px solid #7A7A7A;">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-teal-500/15 text-teal-400 mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Підняття та топ-позиції</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Виділяйте вакансії у пошуку — «Гаряча» або «Топ». Більше переглядів, більше кандидатів у найкоротший термін.</p>
            </div>
        </div>
    </div>
</section>

{{-- Pricing Section --}}
<section class="bg-white py-20 lg:py-28" id="pricing" style="background-image: url('{{ asset('img/bg-main.webp') }}'); background-repeat: repeat;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">Тарифи</h2>
            <p class="text-lg text-slate-500 max-w-2xl mx-auto">Обирайте план, який підходить вашій компанії. Починайте безкоштовно — оновіть, коли будете готові.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($plans as $plan)
                @php
                    $isPopular = $plan->type->value === 'business';
                    $activeJobs = $plan->feature(\App\Enums\PlanFeature::ActiveJobs);
                    $appsPerMonth = $plan->feature(\App\Enums\PlanFeature::ApplicationsPerMonth);
                    $hasAnalytics = $plan->feature(\App\Enums\PlanFeature::Analytics);
                    $hasTemplates = $plan->feature(\App\Enums\PlanFeature::MessageTemplates);
                    $hotPerMonth = $plan->feature(\App\Enums\PlanFeature::HotPerMonth);
                    $topPerMonth = $plan->feature(\App\Enums\PlanFeature::TopPerMonth);
                    $teamMembers = $plan->feature(\App\Enums\PlanFeature::TeamMembers);
                    $hasApi = $plan->feature(\App\Enums\PlanFeature::ApiAccess);
                @endphp
                <div class="relative rounded-3xl p-8 flex flex-col {{ $isPopular ? 'bg-blue-600 text-white shadow-2xl shadow-blue-500/30 -mt-4 -mb-4' : 'bg-white text-slate-900 shadow-sm' }}"
                     style="border: {{ $isPopular ? '2px solid #2563eb' : '1px solid #7A7A7A' }};">
                    @if($isPopular)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-amber-400 text-amber-900 text-xs font-extrabold uppercase tracking-wider px-4 py-1.5 rounded-full shadow">
                            Найпопулярніший
                        </div>
                    @endif

                    <div class="mb-6">
                        <h3 class="text-xl font-extrabold mb-1">{{ $plan->name }}</h3>
                        <div class="flex items-end gap-1 mt-4">
                            <span class="text-4xl font-extrabold">{{ number_format($plan->price_monthly, 0, '.', ' ') }}</span>
                            <span class="text-slate-400 text-sm mb-1.5">грн/міс</span>
                        </div>
                    </div>

                    <ul class="space-y-3 flex-1 mb-8">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $isPopular ? 'text-blue-200' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-sm">
                                @if($activeJobs === 0)
                                    Необмежена кількість вакансій
                                @else
                                    До {{ $activeJobs }} активних {{ $activeJobs === 1 ? 'вакансії' : 'вакансій' }}
                                @endif
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $isPopular ? 'text-blue-200' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-sm">
                                @if($appsPerMonth === 0)
                                    Необмежена кількість відгуків
                                @else
                                    До {{ $appsPerMonth }} відгуків/міс
                                @endif
                            </span>
                        </li>
                        @if($hasAnalytics)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $isPopular ? 'text-blue-200' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-sm">Аналітика та статистика</span>
                        </li>
                        @endif
                        @if($hasTemplates)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $isPopular ? 'text-blue-200' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-sm">Шаблони повідомлень</span>
                        </li>
                        @endif
                        @if($hotPerMonth > 0)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $isPopular ? 'text-blue-200' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-sm">{{ $hotPerMonth }} «Гаряча» вакансія на місяць</span>
                        </li>
                        @endif
                        @if($topPerMonth > 0)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $isPopular ? 'text-blue-200' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-sm">{{ $topPerMonth }} «Топ»-вакансія на місяць</span>
                        </li>
                        @endif
                        @if($teamMembers === 0 || $teamMembers > 1)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $isPopular ? 'text-blue-200' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-sm">
                                @if($teamMembers === 0)
                                    Необмежена кількість HR-менеджерів
                                @else
                                    До {{ $teamMembers }} HR-менеджерів
                                @endif
                            </span>
                        </li>
                        @endif
                        @if($hasApi)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 {{ $isPopular ? 'text-blue-200' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-sm">API-доступ</span>
                        </li>
                        @endif
                    </ul>

                    <a href="{{ route('register') }}"
                       class="w-full text-center font-bold py-3.5 rounded-2xl transition-all duration-200 {{ $isPopular ? 'bg-white text-blue-600 hover:bg-blue-50' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                        Обрати тариф
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Free plan note --}}
        <div class="mt-10 text-center">
            <p class="text-slate-500 text-sm">
                Хочете спочатку спробувати? Безкоштовний план включає 1 активну вакансію та до 10 відгуків.
                <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">Зареєструватись безкоштовно &rarr;</a>
            </p>
        </div>
    </div>
</section>

{{-- Testimonials Section --}}
<section class="bg-slate-50 py-20 lg:py-28" style="background-image: url('{{ asset('img/bg-main.webp') }}'); background-repeat: repeat;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">Нам довіряють компанії</h2>
            <p class="text-lg text-slate-500">Що кажуть роботодавці, які вже використовують MyJob</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl p-7 shadow-sm" style="border: 1px solid #7A7A7A;">
                <div class="flex gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-slate-600 text-sm leading-relaxed mb-5">"Закрили вакансію senior розробника за 3 тижні. Telegram-розсилка дала нам кандидатів, яких ми б не знайшли на інших платформах."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">ОК</div>
                    <div>
                        <div class="font-semibold text-slate-900 text-sm">Олена Коваленко</div>
                        <div class="text-slate-400 text-xs">HR-менеджер, TechUA</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-7 shadow-sm" style="border: 1px solid #7A7A7A;">
                <div class="flex gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-slate-600 text-sm leading-relaxed mb-5">"Зручний кабінет, швидка підтримка. Шаблони повідомлень заощадили нам купу часу — тепер відповідаємо кандидатам у 2 кліки."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-sm">МП</div>
                    <div>
                        <div class="font-semibold text-slate-900 text-sm">Максим Петренко</div>
                        <div class="text-slate-400 text-xs">CEO, Будівельна компанія «Форум»</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-7 shadow-sm" style="border: 1px solid #7A7A7A;">
                <div class="flex gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-slate-600 text-sm leading-relaxed mb-5">"Перейшли з іншого сайту вакансій — різниця відчутна. Більше цільових відгуків, менше «сміття». Рекомендуємо всім ритейлерам."</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-sm">НС</div>
                    <div>
                        <div class="font-semibold text-slate-900 text-sm">Наталія Савченко</div>
                        <div class="text-slate-400 text-xs">HR-директор, Мережа «Аврора»</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FAQ Section --}}
<section class="bg-white py-20 lg:py-28" style="background-image: url('{{ asset('img/bg-main.webp') }}'); background-repeat: repeat;">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">Часті запитання</h2>
        </div>
        <div class="space-y-4" x-data="{ open: null }">
            @php
                $faqs = [
                    ['q' => 'Чи є безкоштовний план?', 'a' => 'Так. Безкоштовний план дозволяє опублікувати 1 активну вакансію та отримати до 10 відгуків на місяць. Ніякої кредитної картки для реєстрації не потрібно.'],
                    ['q' => 'Як швидко вакансія стане видимою?', 'a' => 'Миттєво після публікації. Вакансія одночасно з\'являється на сайті та надсилається підписникам Telegram-каналу MyJob.'],
                    ['q' => 'Можна скасувати підписку?', 'a' => 'Так, у будь-який момент без штрафів. Доступ зберігається до кінця оплаченого місяця.'],
                    ['q' => 'Чи можу я додати кількох HR-менеджерів?', 'a' => 'Так. Тарифи «Бізнес» та «Про» підтримують кількох членів команди. У плані «Про» — без обмежень.'],
                    ['q' => 'Що таке «Гаряча» та «Топ» вакансія?', 'a' => '«Гаряча» вакансія виділяється кольором у списку. «Топ» вакансія закріплюється у верхній частині пошукової видачі. Обидва типи значно збільшують кількість переглядів.'],
                ];
            @endphp

            @foreach($faqs as $index => $faq)
            <div class="rounded-2xl overflow-hidden bg-white" style="border: 1px solid #7A7A7A;" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="w-full flex items-center justify-between px-6 py-5 text-left font-semibold text-slate-900 hover:bg-slate-50 transition-colors duration-150"
                >
                    <span>{{ $faq['q'] }}</span>
                    <svg class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-6 pb-5">
                    <p class="text-slate-500 leading-relaxed">{{ $faq['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="bg-gradient-to-br from-blue-600 to-indigo-700 py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">
            Готові знайти свого ідеального кандидата?
        </h2>
        <p class="text-lg text-blue-100 mb-10 max-w-2xl mx-auto">
            Приєднуйтесь до сотень компаній, що вже знайшли своїх працівників через MyJob.
            Перша вакансія — безкоштовно.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}"
               class="inline-flex items-center justify-center gap-2 bg-white text-blue-700 hover:bg-blue-50 font-bold px-10 py-4 rounded-2xl transition-all duration-200 text-lg shadow-xl hover:-translate-y-0.5">
                Зареєструватись безкоштовно
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
            <a href="{{ route('login') }}"
               class="inline-flex items-center justify-center gap-2 bg-blue-500/30 hover:bg-blue-500/40 border border-white/30 text-white font-semibold px-10 py-4 rounded-2xl transition-all duration-200 text-lg backdrop-blur-sm">
                Увійти
            </a>
        </div>
    </div>
</section>
</x-app-layout>
