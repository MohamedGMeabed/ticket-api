<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\Seat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $register = $this->postJson('/api/register', $payload);
        $register->assertStatus(201);
        $register->assertJsonPath('user.email', 'jane@example.com');

        $login = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $login->assertStatus(200);
        $this->assertNotEmpty($login->json('token'));
    }

    public function test_user_can_list_events_and_seats(): void
    {
        $event = Event::factory()->create();
        $seat = Seat::factory()->for($event)->create(['status' => 'available']);

        $this->getJson('/api/events')->assertOk()->assertJsonFragment(['id' => $event->id]);
        $this->getJson('/api/events/' . $event->id . '/seats')->assertOk()->assertJsonFragment(['id' => $seat->id]);
    }

    public function test_reserved_seat_is_not_available_to_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->for($event)->create(['status' => 'available']);

        $token = $owner->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/reservations', [
                'event_id' => $event->id,
                'seat_ids' => [$seat->id],
            ])
            ->assertCreated();

        $this->withToken($other->createToken('test')->plainTextToken)
            ->postJson('/api/reservations', [
                'event_id' => $event->id,
                'seat_ids' => [$seat->id],
            ])
            ->assertStatus(409);
    }

    public function test_expired_reservation_releases_the_seat(): void
    {
        Carbon::setTestNow(now()->subMinutes(31));

        $user = User::factory()->create();
        $event = Event::factory()->create();
        $seat = Seat::factory()->for($event)->create(['status' => 'available']);

        $reservation = Reservation::factory()->for($user)->for($event)->create([
            'status' => 'pending',
            'expires_at' => now()->subMinutes(1),
        ]);
        $reservation->seats()->attach($seat->id);
        $seat->update(['status' => 'held']);

        $this->artisan('app:expire-reservations')->assertSuccessful();

        $this->assertSame('expired', $reservation->fresh()->status);
        $this->assertSame('available', $seat->fresh()->status);

        Carbon::setTestNow();
    }
}
