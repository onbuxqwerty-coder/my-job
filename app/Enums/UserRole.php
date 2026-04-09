<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Candidate = 'candidate';
    case Employer = 'employer';
    case Admin = 'admin';

    public function label(): string
    {
        return match($this) {
            self::Candidate => 'Кандидат',
            self::Employer  => 'Роботодавець',
            self::Admin     => 'Адміністратор',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
