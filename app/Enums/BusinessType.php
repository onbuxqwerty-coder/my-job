<?php

declare(strict_types=1);

namespace App\Enums;

enum BusinessType: string
{
    case Legal      = 'legal';
    case Individual = 'individual';

    public function label(): string
    {
        return match($this) {
            self::Legal      => 'Юридична особа (ЄДРПОУ)',
            self::Individual => 'ФОП (ІПН)',
        };
    }

    public function taxIdLabel(): string
    {
        return match($this) {
            self::Legal      => 'ЄДРПОУ',
            self::Individual => 'ІПН',
        };
    }

    public function taxIdLength(): int
    {
        return match($this) {
            self::Legal      => 8,
            self::Individual => 10,
        };
    }
}
