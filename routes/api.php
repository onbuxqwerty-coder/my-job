<?php

declare(strict_types=1);

use App\Http\Controllers\CityController;
use Illuminate\Support\Facades\Route;

Route::prefix('cities')->group(function (): void {
    Route::get('/', [CityController::class, 'index']);
    Route::get('/search', [CityController::class, 'search']);
    Route::post('/nearest', [CityController::class, 'nearest']);
});
