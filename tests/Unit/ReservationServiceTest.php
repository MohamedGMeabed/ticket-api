<?php

namespace Tests\Unit;

use App\Domain\Reservations\Exceptions\SeatUnavailableException;
use App\Domain\Reservations\ReservationService;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReservationService::class);
    }

    public function test_it_reserves_available_seats(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->for($event)->create(['status' => 'available']);

        $reservation = $this->service->reserve($user, $event, [$seat->id]);

        $this->assertSame('pending', $reservation->status);
        $this->assertTrue($reservation->seats->contains($seat));
        $this->assertSame('held', $seat->fresh()->status);
    }

    public function test_it_throws_when_a_seat_is_already_held(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->for($event)->create(['status' => 'available']);

        Reservation::factory()->for($user)->for($event)->create([
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ])->seats()->attach($seat->id);

        $seat->updateQuietly(['status' => 'held']);

        $this->expectException(SeatUnavailableException::class);

        $this->service->reserve($user, $event, [$seat->id]);
    }

    public function test_it_expires_only_stale_reservations(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $expiredSeat = Seat::factory()->for($event)->create(['status' => 'held']);
        $activeSeat = Seat::factory()->for($event)->create(['status' => 'held']);

        $expiredReservation = Reservation::factory()->for($user)->for($event)->create([
            'status' => 'pending',
            'expires_at' => now()->subMinutes(1),
        ]);
        $expiredReservation->seats()->attach($expiredSeat->id);

        $activeReservation = Reservation::factory()->for($user)->for($event)->create([
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);
        $activeReservation->seats()->attach($activeSeat->id);

        $this->service->expireStale();

        $this->assertSame('expired', $expiredReservation->fresh()->status);
        $this->assertSame('pending', $activeReservation->fresh()->status);
        $this->assertSame('available', $expiredSeat->fresh()->status);
        $this->assertSame('held', $activeSeat->fresh()->status);
    }
}
