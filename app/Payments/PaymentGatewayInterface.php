<?php

namespace App\Payments;

interface PaymentGatewayInterface
{
    public function createCheckoutSession(object $payment, string $successUrl, string $cancelUrl): object;

    public function verifyWebhook(object $request): object;
}
