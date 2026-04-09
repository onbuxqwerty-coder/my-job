<?php

declare(strict_types=1);

namespace App\Enums;

enum Language: string
{
    case Ukrainian = 'uk';
    case English   = 'en';
    case German    = 'de';
    case Spanish   = 'es';
    case Polish    = 'pl';

    public function label(): string
    {
        return match($this) {
            self::Ukrainian => 'Українська',
            self::English   => 'Англійська',
            self::German    => 'Німецька',
            self::Spanish   => 'Іспанська',
            self::Polish    => 'Польська',
        };
    }
}
