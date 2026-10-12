<?php

namespace Database\Factories;

use App\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('+1 week', '+6 months');
        $name = 'Fachtagung '.fake()->unique()->city();

        return [
            'cas_id' => strtoupper(fake()->unique()->bothify(str_repeat('?', 32))),
            'etag' => fake()->sha1(),
            'slug' => str($name)->slug()->toString(),
            'type' => 'fachtagung',
            'name' => $name,
            'title' => null,
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+1 day'),
            'status' => 'scheduled',
            'venue' => null,
            'city' => fake()->city(),
            'location_note' => null,
            'description' => fake()->sentence(12),
            'registration_url' => null,
            'synced_at' => now(),
        ];
    }

    public function messe(): static
    {
        return $this->state(fn (): array => ['type' => 'messe', 'venue' => 'Messe Düsseldorf', 'city' => 'Düsseldorf']);
    }

    public function past(): static
    {
        return $this->state(fn (): array => ['starts_on' => now()->subMonths(2), 'ends_on' => now()->subMonths(2)->addDay()]);
    }
}
