<?php

namespace App\Payments\DTO;

class CheckoutSession
{
    public function __construct(
        public string $url,
        public string $providerReference,
    ) {
    }
}
