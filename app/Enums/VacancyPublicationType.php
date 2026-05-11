<?php

declare(strict_types=1);

namespace App\Enums;

enum VacancyPublicationType: string
{
    case Standard  = 'standard';
    case Anonymous = 'anonymous';

    public function label(): string
    {
        return match($this) {
            self::Standard  => 'Звичайна',
            self::Anonymous => 'Анонімна',
        };
    }
}
