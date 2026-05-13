<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannel: string
{
    case Email    = 'email';
    case Telegram = 'telegram';

    public function label(): string
    {
        return match($this) {
            self::Email    => 'Email',
            self::Telegram => 'Telegram',
        };
    }
}
