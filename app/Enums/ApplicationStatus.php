<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending   = 'pending';
    case Screening = 'screening';
    case Interview = 'interview';
    case Hired     = 'hired';
    case Rejected  = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Новий',
            self::Screening => 'Розгляд',
            self::Interview => 'Співбесіда',
            self::Hired     => 'Прийнятий',
            self::Rejected  => 'Відхилений',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function color(): string
    {
        return match($this) {
            self::Pending   => 'gray',
            self::Screening => 'blue',
            self::Interview => 'yellow',
            self::Hired     => 'green',
            self::Rejected  => 'red',
        };
    }
}
