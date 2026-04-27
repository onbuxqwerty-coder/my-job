<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-vacancy-alerts')->hourly();
Schedule::command('app:deactivate-expired-featured')->daily();
Schedule::command('app:cleanup-temp-uploads --hours=24')->daily();

Schedule::command('vacancies:notify-expiring')
    ->hourly()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('vacancies.notify-expiring');

Schedule::command('vacancies:expire')
    ->hourly()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->onOneServer()
    ->name('vacancies.expire')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::channel('vacancies')->error('Scheduled vacancies:expire failed');
    });
