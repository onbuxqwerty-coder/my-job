<?php

declare(strict_types=1);

namespace App\Enums;

enum VacancyStatus: string
{
    case Draft    = 'draft';
    case Active   = 'active';
    case Expired  = 'expired';
    case Archived = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Draft    => 'Чернетка',
            self::Active   => 'Активна',
            self::Expired  => 'Завершена',
            self::Archived => 'Архів',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft    => 'gray',
            self::Active   => 'success',
            self::Expired  => 'warning',
            self::Archived => 'danger',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft    => 'bg-gray-100 text-gray-700',
            self::Active   => 'bg-green-100 text-green-700',
            self::Expired  => 'bg-yellow-100 text-yellow-700',
            self::Archived => 'bg-red-100 text-red-700',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::Draft    => 'Не опубліковано — бачить лише автор.',
            self::Active   => 'Активна публікація на сайті.',
            self::Expired  => 'Час публікації вийшов, доступна за прямим URL для SEO.',
            self::Archived => 'Знята з пошуку, повертає 404 за прямим URL.',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
            ->toArray();
    }

    public static function publicCases(): array
    {
        return [self::Active, self::Expired];
    }
}
