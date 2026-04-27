<?php

declare(strict_types=1);

namespace App\Payments;

use App\Models\Vacancy;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTOs\CheckoutData;

class CheckoutService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    public function createVacancyExtensionCheckout(
        Vacancy $vacancy,
        int $days,
        ?string $successUrl = null,
        ?string $cancelUrl  = null,
    ): string {
        $prices        = config('payments.prices');
        $amountKopecks = $prices[$days] ?? throw new \InvalidArgumentException("Unknown plan: {$days} days");

        $orderId = self::buildOrderId($vacancy->id, $days);

        $data = new CheckoutData(
            vacancy:       $vacancy,
            days:          $days,
            amountKopecks: $amountKopecks,
            currency:      'UAH',
            orderId:       $orderId,
            description:   "Публікація вакансії «{$vacancy->title}» на {$days} днів",
            successUrl:    $successUrl ?? route('employer.vacancies.payment.success', $vacancy),
            cancelUrl:     $cancelUrl  ?? route('employer.vacancies.payment.cancel', $vacancy),
            webhookUrl:    route('webhooks.payments', ['gateway' => $this->gateway->name()]),
            customerEmail: $vacancy->company?->user?->email,
            customerName:  $vacancy->company?->name,
        );

        return $this->gateway->createCheckout($data);
    }

    /**
     * Формат: vac_{vacancyId}_{days}_{randomSuffix}
     * Приклад: vac_42_30_a3f7k2
     * Кодує vacancy_id і days у самому ID — для провайдерів без metadata.
     */
    public static function buildOrderId(int $vacancyId, int $days): string
    {
        return sprintf('vac_%d_%d_%s', $vacancyId, $days, substr(uniqid(), -6));
    }

    /** @return array{0: int|null, 1: int|null} */
    public static function parseOrderId(string $orderId): array
    {
        if (preg_match('/^vac_(\d+)_(\d+)_/', $orderId, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [null, null];
    }
}
