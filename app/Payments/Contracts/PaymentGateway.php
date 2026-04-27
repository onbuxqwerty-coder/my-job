<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Payments\DTOs\CheckoutData;
use App\Payments\DTOs\PaymentResult;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Ім'я провайдера — збігається з ключем у config/payments.php.
     */
    public function name(): string;

    /**
     * Створити сесію оплати і повернути URL для редіректу.
     *
     * @throws \App\Payments\Exceptions\PaymentGatewayException
     */
    public function createCheckout(CheckoutData $data): string;

    /**
     * Перевірити підпис webhook-запиту та розпарсити результат.
     *
     * @throws \App\Payments\Exceptions\InvalidWebhookSignatureException
     * @throws \App\Payments\Exceptions\PaymentGatewayException
     */
    public function parseWebhook(Request $request): PaymentResult;

    /**
     * HTTP-відповідь, яку очікує провайдер при успішній обробці.
     */
    public function successResponse(): \Illuminate\Http\Response;
}
