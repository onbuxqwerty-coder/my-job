<?php

declare(strict_types=1);

use App\Telegram\Callbacks\AlertToggleCallback;
use App\Telegram\Commands\AlertsCommand;
use App\Telegram\Commands\StartCommand;
use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

Route::post('/telegram/webhook', function (Nutgram $bot): void {
    $bot->onCommand('start', StartCommand::class);
    $bot->onCommand('alerts', AlertsCommand::class);
    $bot->onCallbackQueryData('alert_toggle:[0-9]+', AlertToggleCallback::class);
    $bot->run();
})->middleware('throttle:30,1');
