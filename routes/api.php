<?php

declare(strict_types=1);

use App\Http\Controllers\CityController;
use App\Http\Controllers\TelegramAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('telegram/auth')->group(function (): void {
    Route::post('/init', [TelegramAuthController::class, 'init'])->middleware('throttle:20,1');
    Route::get('/status/{token}', [TelegramAuthController::class, 'status'])->middleware('throttle:60,1');
});

Route::prefix('cities')->group(function (): void {
    Route::get('/', [CityController::class, 'index']);
    Route::get('/search', [CityController::class, 'search']);
    Route::post('/nearest', [CityController::class, 'nearest']);
});
