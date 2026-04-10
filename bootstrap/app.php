<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/telegram.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            '/telegram/webhook',
            '/stripe/webhook',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Send critical 500 errors to Telegram (production only)
        $exceptions->report(function (\Throwable $e): void {
            if (app()->environment('production') && config('telegram.token') && config('telegram.error_chat_id')) {
                // Skip non-critical HTTP exceptions (404, 403, etc.)
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() < 500) {
                    return;
                }

                $token  = config('telegram.token');
                $chatId = config('telegram.error_chat_id');
                $text   = sprintf(
                    "🚨 *%s Error*\n\n`%s`\n\n_%s:%d_\n\n[%s]",
                    config('app.name'),
                    mb_substr(addcslashes($e->getMessage(), '`_*['), 0, 300),
                    basename($e->getFile()),
                    $e->getLine(),
                    request()->url(),
                );

                try {
                    \Illuminate\Support\Facades\Http::post(
                        "https://api.telegram.org/bot{$token}/sendMessage",
                        ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown'],
                    );
                } catch (\Throwable) {
                    // Never let error reporting itself crash the app
                }
            }
        });
    })->create();
