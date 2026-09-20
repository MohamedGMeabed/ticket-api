<?php

namespace App\Http\Controllers;

use App\Domain\Reservations\Exceptions\SeatUnavailableException;
use App\Domain\Reservations\ReservationService;
use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct(protected ReservationService $reservationService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['integer', 'exists:seats,id'],
        ]);

        $event = Event::findOrFail($validated['event_id']);

        try {
            $reservation = $this->reservationService->reserve($request->user(), $event, $validated['seat_ids']);
        } catch (SeatUnavailableException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'reservation' => $reservation->load('seats'),
            'payment_url' => route('checkout', ['reservation' => $reservation->id]),
        ], 201);
    }

    public function show(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        return $reservation->load(['seats', 'event']);
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($reservation) {
            $reservation->loadMissing('seats');
            foreach ($reservation->seats as $seat) {
                $seat->updateQuietly(['status' => 'available']);
            }
            $reservation->status = 'cancelled';
            $reservation->expires_at = null;
            $reservation->save();
            $reservation->seats()->detach();
        });

        return response()->json(['message' => 'Reservation cancelled.']);
    }
}
