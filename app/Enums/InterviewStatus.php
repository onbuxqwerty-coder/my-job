<?php

declare(strict_types=1);

namespace App\Enums;

enum InterviewStatus: string
{
    case Scheduled   = 'scheduled';
    case Confirmed   = 'confirmed';
    case Rescheduled = 'rescheduled';
    case Cancelled   = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Scheduled   => 'Заплановано',
            self::Confirmed   => 'Підтверджено',
            self::Rescheduled => 'Перенесено',
            self::Cancelled   => 'Скасовано',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Scheduled   => 'blue',
            self::Confirmed   => 'green',
            self::Rescheduled => 'yellow',
            self::Cancelled   => 'red',
        };
    }
}
