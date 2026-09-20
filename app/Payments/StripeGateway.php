<?php

namespace App\Payments;

use App\Payments\DTO\CheckoutSession;
use App\Payments\DTO\PaymentWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class StripeGateway implements PaymentGatewayInterface
{
    public function createCheckoutSession(object $payment, string $successUrl, string $cancelUrl): object
    {
        // In production, this would call Stripe API to create a Checkout Session
        // For now, simulate with a mock URL that would redirect to Stripe's hosted page
        $reference = 'stripe_' . uniqid();

        $url = URL::route('mock.checkout', ['reference' => $reference]) . '?success=' . urlencode($successUrl) . '&cancel=' . urlencode($cancelUrl);

        return new CheckoutSession(
            url: $url,
            providerReference: $reference,
        );
    }

    public function verifyWebhook(object $request): object
    {
        $payload = $request instanceof Request ? $request->all() : (array) $request;

        // In production, verify stripe-signature header against webhook secret
        // $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);

        return new PaymentWebhookEvent(
            reference: $payload['reference'] ?? '',
            status: $payload['status'] ?? 'succeeded',
        );
    }
}
