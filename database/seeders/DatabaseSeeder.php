<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $events = [
            [
                'name' => 'Midnight Jazz Night',
                'venue' => 'Grand Hall',
                'starts_at' => now()->addDays(3)->setTime(19, 30),
            ],
            [
                'name' => 'City Film Festival',
                'venue' => 'Riverside Theater',
                'starts_at' => now()->addDays(6)->setTime(18, 0),
            ],
            [
                'name' => 'Champions Live',
                'venue' => 'Metro Arena',
                'starts_at' => now()->addDays(9)->setTime(20, 0),
            ],
        ];

        foreach ($events as $eventData) {
            $event = Event::create($eventData);

            $sections = [
                'A' => ['rows' => ['A', 'B', 'C'], 'price' => 4500],
                'B' => ['rows' => ['A', 'B', 'C', 'D'], 'price' => 3500],
                'C' => ['rows' => ['A', 'B'], 'price' => 2500],
            ];

            foreach ($sections as $section => $meta) {
                foreach ($meta['rows'] as $row) {
                    for ($number = 1; $number <= 12; $number++) {
                        Seat::create([
                            'event_id' => $event->id,
                            'section' => $section,
                            'row' => $row,
                            'number' => $number,
                            'price_cents' => $meta['price'],
                            'status' => 'available',
                        ]);
                    }
                }
            }
        }
    }
}
