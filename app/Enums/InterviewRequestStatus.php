<?php

declare(strict_types=1);

namespace App\Enums;

enum InterviewRequestStatus: string
{
    case Pending  = 'pending';
    case Answered = 'answered';
    case Expired  = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Очікує відповіді',
            self::Answered => 'Відповів',
            self::Expired  => 'Термін минув',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending  => 'yellow',
            self::Answered => 'green',
            self::Expired  => 'red',
        };
    }
}
