<?php

declare(strict_types=1);

return [
    'token'         => env('TELEGRAM_TOKEN', ''),
    'webhook_url'   => env('TELEGRAM_WEBHOOK_URL', ''),
    // Chat ID (or @username) that receives 500-error alerts in production
    'error_chat_id' => env('TELEGRAM_ERROR_CHAT_ID', ''),
];
