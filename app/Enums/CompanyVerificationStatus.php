<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyVerificationStatus: string
{
    case Unverified = 'unverified';
    case Verified   = 'verified';
    case Rejected   = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Unverified => 'Не перевірено',
            self::Verified   => 'Верифіковано',
            self::Rejected   => 'Відхилено',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Unverified => 'gray',
            self::Verified   => 'success',
            self::Rejected   => 'danger',
        };
    }
}
