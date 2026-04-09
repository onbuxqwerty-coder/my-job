<?php

declare(strict_types=1);

namespace App\Enums;

enum InterviewType: string
{
    case Video    = 'video';
    case Phone    = 'phone';
    case InPerson = 'in_person';
    case Other    = 'other';

    public function label(): string
    {
        return match($this) {
            self::Video    => 'Відеозустріч',
            self::Phone    => 'Телефонна розмова',
            self::InPerson => 'Очна зустріч (офіс)',
            self::Other    => 'Інший формат',
        };
    }

    public function needsLink(): bool
    {
        return $this === self::Video;
    }

    public function needsAddress(): bool
    {
        return $this === self::InPerson;
    }
}
