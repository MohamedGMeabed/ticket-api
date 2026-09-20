<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'venue' => $this->faker->company() . ' Arena',
            'starts_at' => $this->faker->dateTimeBetween('+1 day', '+10 days'),
        ];
    }
}
