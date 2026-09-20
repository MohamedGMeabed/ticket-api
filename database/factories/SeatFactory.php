<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'section' => $this->faker->randomElement(['A', 'B', 'C']),
            'row' => $this->faker->randomLetter() . $this->faker->randomDigit(),
            'number' => $this->faker->numberBetween(1, 25),
            'price_cents' => $this->faker->numberBetween(1500, 8500),
            'status' => 'available',
        ];
    }
}
