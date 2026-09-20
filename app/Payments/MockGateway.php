<?php

namespace App\Payments;

use App\Payments\DTO\CheckoutSession;
use App\Payments\DTO\PaymentWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class MockGateway implements PaymentGatewayInterface
{
    public function createCheckoutSession(object $payment, string $successUrl, string $cancelUrl): object
    {
        $reference = 'mock_' . uniqid();

        $url = URL::route('mock.checkout', ['reference' => $reference]) . '?success=' . urlencode($successUrl) . '&cancel=' . urlencode($cancelUrl);

        return new CheckoutSession(
            url: $url,
            providerReference: $reference,
        );
    }

    public function verifyWebhook(object $request): object
    {
        $payload = $request instanceof Request ? $request->all() : (array) $request;

        return new PaymentWebhookEvent(
            reference: $payload['reference'] ?? '',
            status: $payload['status'] ?? 'succeeded',
        );
    }
}
