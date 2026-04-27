<?php

declare(strict_types=1);

namespace App\Payments\DTOs;

final readonly class PaymentResult
{
    public function __construct(
        public bool $isPaid,
        public string $gatewayName,
        public string $externalEventId,
        public string $orderId,
        public int $amountKopecks,
        public string $currency,
        public ?string $vacancyId,
        public ?int $days,
        public ?string $failureReason = null,
    ) {}
}
