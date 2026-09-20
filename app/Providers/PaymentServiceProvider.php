<?php

namespace App\Providers;

use App\Payments\MockGateway;
use App\Payments\PaymentGatewayInterface;
use App\Payments\StripeGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return config('services.payment.driver') === 'stripe'
                ? new StripeGateway()
                : new MockGateway();
        });
    }
}
