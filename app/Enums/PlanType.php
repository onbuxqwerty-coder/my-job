<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanType: string
{
    case Free     = 'free';
    case Start    = 'start';
    case Business = 'business';
    case Pro      = 'pro';
}
