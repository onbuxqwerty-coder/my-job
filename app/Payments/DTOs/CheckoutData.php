<?php

declare(strict_types=1);

namespace App\Payments\DTOs;

use App\Models\Vacancy;

final readonly class CheckoutData
{
    public function __construct(
        public Vacancy $vacancy,
        public int $days,
        public int $amountKopecks,
        public string $currency,
        public string $orderId,
        public string $description,
        public string $successUrl,
        public string $cancelUrl,
        public string $webhookUrl,
        public ?string $customerEmail = null,
        public ?string $customerName  = null,
    ) {}

    public function amountUah(): float
    {
        return $this->amountKopecks / 100;
    }

    public function amountMinorUnits(): int
    {
        return $this->amountKopecks;
    }
}
