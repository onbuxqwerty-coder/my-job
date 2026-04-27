<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Payments\Contracts\PaymentGateway;

class PaymentGatewayRegistry
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->name()] = $gateway;
    }

    public function get(string $name): ?PaymentGateway
    {
        return $this->gateways[$name] ?? null;
    }
}
