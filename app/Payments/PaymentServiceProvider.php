<?php

declare(strict_types=1);

namespace App\Payments;

use App\Http\Controllers\Payments\PaymentGatewayRegistry;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\LiqPayGateway;
use App\Payments\Gateways\MonoPayGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\Gateways\WayForPayGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/payments.php', 'payments');

        $this->app->singleton(PaymentGatewayRegistry::class, function () {
            $registry = new PaymentGatewayRegistry();
            $registry->register($this->app->make(StripeGateway::class));
            $registry->register($this->app->make(MonoPayGateway::class));
            $registry->register($this->app->make(WayForPayGateway::class));
            $registry->register($this->app->make(LiqPayGateway::class));
            return $registry;
        });

        $this->app->bind(PaymentGateway::class, function () {
            $name = config('payments.default', 'mono');
            return $this->app->make(PaymentGatewayRegistry::class)->get($name)
                ?? throw new \RuntimeException("Payment gateway '{$name}' not registered.");
        });

        $this->app->bind(CheckoutService::class, function () {
            return new CheckoutService($this->app->make(PaymentGateway::class));
        });
    }

    public function boot(): void {}
}
