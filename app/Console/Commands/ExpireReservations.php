<?php

namespace App\Console\Commands;

use App\Domain\Reservations\ReservationService;
use Illuminate\Console\Command;

class ExpireReservations extends Command
{
    protected $signature = 'app:expire-reservations';

    protected $description = 'Expire stale pending reservations';

    public function handle(ReservationService $service): int
    {
        $count = $service->expireStale();

        $this->info("Expired {$count} reservations.");

        return self::SUCCESS;
    }
}
