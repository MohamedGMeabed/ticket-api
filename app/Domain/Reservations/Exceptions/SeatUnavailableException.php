<?php

namespace App\Domain\Reservations\Exceptions;

use RuntimeException;

class SeatUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'One or more seats are no longer available.')
    {
        parent::__construct($message, 409);
    }
}
