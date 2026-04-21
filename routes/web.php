<?php

declare(strict_types=1);

use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TelegramAuthController;
use App\Models\Category;
use App\Models\Interview;
use App\Models\Resume;
use App\Models\Vacancy;
use App\Services\InterviewService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// ── Public ─────────────────────────────────────────────────────────────────
Volt::route('/', 'pages.jobs.index')->name('home');

// ── Interview confirmation (public, token-based) ────────────────────────────
Route::get('/interview/{token}/confirm', function (string $token, InterviewService $service) {
    try {
        $interview = $service->confirm($token);
        return view('interview.response', [
            'message' => 'Участь підтверджено! Чекаємо вас ' . $interview->scheduled_at->format('d.m.Y о H:i') . '.',
            'type'    => 'success',
        ]);
    } catch (\Throwable) {
        abort(404);
    }
})->name('interview.confirm');

Route::get('/interview/{token}/cancel', function (string $token, InterviewService $service) {
    $interview = Interview::where('confirm_token', $token)->firstOrFail();
    $service->cancel($interview, 'Скасовано кандидатом');
    return view('interview.response', [
        'message' => 'Ваша участь скасована. Якщо це помилка — зв\'яжіться з нами.',
        'type'    => 'warning',
    ]);
})->name('interview.cancel');
Volt::route('/jobs/{vacancy:slug}', 'pages.jobs.show')->name('jobs.show');

// Sitemap (cached 24 h)
Route::get('/sitemap.xml', function () {
    $xml = Cache::remember('sitemap', 86400, function () {
        $vacancies  = Vacancy::where('is_active', true)->get(['slug', 'updated_at']);
        $categories = Category::orderBy('position')->get(['id', 'updated_at']);

        return response()->view('sitemap', compact('vacancies', 'categories'))->getContent();
    });

    return response($xml)->header('Content-Type', 'application/xml');
})->name('sitemap');

// ── Telegram Auth ───────────────────────────────────────────────────────────
Route::get('/telegram/auth/login/{token}', [TelegramAuthController::class, 'login'])
    ->name('telegram.auth.login')
    ->middleware('throttle:10,1');

// ── Social Auth ─────────────────────────────────────────────────────────────
Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\SocialAuthController::class, 'redirect'])
    ->name('social.redirect');
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\SocialAuthController::class, 'callback'])
    ->name('social.callback');

// ── Auth ────────────────────────────────────────────────────────────────────
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// ── Employer Dashboard ──────────────────────────────────────────────────────
Route::middleware(['auth', 'role:employer'])
    ->prefix('dashboard/employer')
    ->name('employer.')
    ->group(function () {
        Volt::route('/', 'pages.employer.dashboard')->name('dashboard');
        Volt::route('/profile', 'pages.employer.profile')->name('profile');
        Volt::route('/applicants/{vacancyId}', 'pages.employer.applicants')->name('applicants');
        Volt::route('/candidates', 'pages.employer.candidates')->name('candidates');
        Volt::route('/candidates/{applicationId}', 'pages.employer.candidate-detail')->name('candidate.detail');
        Volt::route('/templates', 'pages.employer.message-templates')->name('message.templates');
        Volt::route('/analytics', 'pages.employer.analytics')->name('analytics');
        Volt::route('/vacancies/create', 'pages.employer.vacancies.edit')->name('vacancies.create');
        Volt::route('/vacancies/{vacancyId}/edit', 'pages.employer.vacancies.edit')->name('vacancies.edit');

        // Stripe Checkout redirect
        Route::get('/vacancies/{vacancy}/promote', function (Vacancy $vacancy, PaymentService $payment) {
            return redirect($payment->createVacancyPromoCheckout($vacancy));
        })->name('vacancies.promote');
    });

// ── Resume Wizard ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:candidate'])
    ->prefix('resumes')
    ->name('resumes.')
    ->group(function () {
        Route::get('/create', function () {
            $resume = auth()->user()->resumes()->create([
                'title'  => 'Нове резюме',
                'status' => 'draft',
            ]);
            return view('resumes.create', compact('resume'));
        })->name('create');

        Route::get('/{resume}/edit', function (Resume $resume) {
            abort_unless(auth()->id() === $resume->user_id, 403);
            return view('resumes.edit', compact('resume'));
        })->name('edit');
    });

// ── Seeker Dashboard ────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:candidate'])
    ->prefix('dashboard/seeker')
    ->name('seeker.')
    ->group(function () {
        Volt::route('/', 'pages.seeker.dashboard')->name('dashboard');
        Volt::route('/applications', 'pages.seeker.applications')->name('applications');
        Volt::route('/applications/{applicationId}', 'pages.seeker.application-detail')->name('application.detail');
        Volt::route('/interviews', 'pages.seeker.interviews')->name('interviews');
        Volt::route('/profile', 'pages.seeker.profile')->name('profile');
    });

// ── Payment callbacks ───────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/payment/success', fn() => view('payment.success'))->name('payment.success');
    Route::get('/payment/cancel', fn() => view('payment.cancel'))->name('payment.cancel');
});

// ── Stripe Webhook (no CSRF, signed by Stripe) ──────────────────────────────
Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook')
    ->middleware('throttle:60,1');

// ── Health Check ────────────────────────────────────────────────────────────
Route::get('/health', function () {
    // Check DB
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $db = 'ok';
    } catch (\Throwable) {
        $db = 'error';
    }

    // Check cache (database driver)
    try {
        \Illuminate\Support\Facades\Cache::put('_health', 1, 5);
        $cache = \Illuminate\Support\Facades\Cache::get('_health') === 1 ? 'ok' : 'error';
    } catch (\Throwable) {
        $cache = 'error';
    }

    $status = ($db === 'ok' && $cache === 'ok') ? 200 : 503;

    return response()->json([
        'status' => $status === 200 ? 'ok' : 'degraded',
        'db'     => $db,
        'cache'  => $cache,
    ], $status);
})->name('health');

require __DIR__ . '/auth.php';
