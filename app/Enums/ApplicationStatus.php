<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending   = 'pending';
    case Viewed    = 'viewed';
    case Reviewing = 'reviewing';
    case Screening = 'screening';
    case Interview = 'interview';
    case Offered   = 'offered';
    case Hired     = 'hired';
    case Rejected  = 'rejected';
    case Withdrawn = 'withdrawn';

    /** @return array<string> */
    public function allowedActors(): array
    {
        return match($this) {
            self::Screening, self::Interview, self::Hired,
            self::Reviewing, self::Offered                => ['employer'],
            self::Viewed                                  => [],
            self::Withdrawn                               => ['seeker'],
            self::Rejected                                => ['employer', 'seeker'],
            default                                       => [],
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Надіслано',
            self::Viewed    => 'Переглянуто',
            self::Reviewing => 'На розгляді',
            self::Screening => 'Розгляд',
            self::Interview => 'Інтерв\'ю',
            self::Offered   => 'Оффер',
            self::Hired     => 'Прийнятий',
            self::Rejected  => 'Відмовлено',
            self::Withdrawn => 'Відкликано',
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
            self::Viewed    => 'blue',
            self::Reviewing => 'indigo',
            self::Screening => 'blue',
            self::Interview => 'yellow',
            self::Offered   => 'green',
            self::Hired     => 'green',
            self::Rejected  => 'red',
            self::Withdrawn => 'orange',
        };
    }
}
