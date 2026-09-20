<?php

namespace App\Domain\Reservations;

use App\Domain\Reservations\Exceptions\SeatUnavailableException;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function reserve(User $user, Event $event, array $seatIds): Reservation
    {
        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));

        return DB::transaction(function () use ($user, $event, $seatIds) {
            $seats = Seat::where('event_id', $event->id)
                ->whereIn('id', $seatIds)
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($seatIds)) {
                throw new SeatUnavailableException('Selected seats do not belong to the event.');
            }

            $activeSeatIds = Reservation::query()
                ->where('event_id', $event->id)
                ->whereIn('status', ['pending'])
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->join('reservation_seats', 'reservations.id', '=', 'reservation_seats.reservation_id')
                ->pluck('reservation_seats.seat_id')
                ->all();

            $takenSeatIds = array_intersect($seatIds, $activeSeatIds);

            if (! empty($takenSeatIds)) {
                throw new SeatUnavailableException('One or more seats are already reserved.');
            }

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(30),
            ]);

            $reservation->seats()->attach($seats->pluck('id')->all());

            foreach ($seats as $seat) {
                $seat->updateQuietly(['status' => 'held']);
            }

            return $reservation->fresh(['seats']);
        });
    }

    public function expireStale(): int
    {
        $expiredReservations = Reservation::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredReservations as $reservation) {
            $this->release($reservation, 'expired');
            $count++;
        }

        return $count;
    }

    public function markPaid(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $reservation->loadMissing('seats');
            $reservation->status = 'paid';
            $reservation->expires_at = null;
            $reservation->save();

            foreach ($reservation->seats as $seat) {
                $seat->updateQuietly(['status' => 'booked']);
            }
        });
    }

    public function release(Reservation $reservation, string $status = 'cancelled'): void
    {
        DB::transaction(function () use ($reservation, $status) {
            $reservation->loadMissing('seats');

            foreach ($reservation->seats as $seat) {
                $seat->updateQuietly(['status' => 'available']);
            }

            $reservation->status = $status;
            $reservation->expires_at = null;
            $reservation->save();

            $reservation->seats()->detach();
        });
    }
}
