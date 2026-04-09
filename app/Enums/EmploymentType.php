<?php

declare(strict_types=1);

namespace App\Enums;

enum EmploymentType: string
{
    case FullTime = 'full-time';
    case PartTime = 'part-time';
    case Remote   = 'remote';
    case Hybrid   = 'hybrid';
    case Contract = 'contract';

    public function label(): string
    {
        return match($this) {
            self::FullTime => 'Повна зайнятість',
            self::PartTime => 'Часткова зайнятість',
            self::Remote   => 'Віддалено',
            self::Hybrid   => 'Гібрид',
            self::Contract => 'Контракт',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
