<?php

namespace App\Payments\DTO;

class PaymentWebhookEvent
{
    public function __construct(
        public string $reference,
        public string $status,
    ) {
    }
}
