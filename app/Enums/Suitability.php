<?php

declare(strict_types=1);

namespace App\Enums;

enum Suitability: string
{
    case NoExperience  = 'no_experience';
    case NoResume      = 'no_resume';
    case Students      = 'students';
    case Disabilities  = 'disabilities';
    case Pensioners    = 'pensioners';

    public function label(): string
    {
        return match($this) {
            self::NoExperience => 'Кандидатам без досвіду',
            self::NoResume     => 'Кандидатам без резюме',
            self::Students     => 'Студентам',
            self::Disabilities => 'Людям з інвалідністю',
            self::Pensioners   => 'Пенсіонерам',
        };
    }
}
