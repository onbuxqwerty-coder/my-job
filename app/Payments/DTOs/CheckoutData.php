<?php

declare(strict_types=1);

namespace App\Payments\DTOs;

use App\Models\Vacancy;

final readonly class CheckoutData
{
    public function __construct(
        public int $amountKopecks,
        public string $currency,
        public string $orderId,
        public string $description,
        public string $successUrl,
        public string $cancelUrl,
        public string $webhookUrl,
        public ?Vacancy $vacancy     = null,
        public ?int $days            = null,
        public ?int $planId          = null,
        public ?int $userId          = null,
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

    public function isPlanSubscription(): bool
    {
        return $this->planId !== null && $this->userId !== null;
    }
}
