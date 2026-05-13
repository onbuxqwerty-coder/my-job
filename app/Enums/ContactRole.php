<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactRole: string
{
    case Seeker      = 'seeker';
    case Employer    = 'employer';
    case Partnership = 'partnership';
    case Other       = 'other';

    public function label(): string
    {
        return match($this) {
            self::Seeker      => 'Шукаю роботу',
            self::Employer    => 'Роботодавець',
            self::Partnership => 'Партнерство',
            self::Other       => 'Інше',
        };
    }

    public function recipientEmail(): string
    {
        return match($this) {
            self::Employer    => 'sales@myjob.co.ua',
            self::Partnership => 'partnership@myjob.co.ua',
            default           => 'support@myjob.co.ua',
        };
    }
}
