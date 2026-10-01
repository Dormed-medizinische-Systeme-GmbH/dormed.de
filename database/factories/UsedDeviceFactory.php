<?php

namespace Database\Factories;

use App\UsedDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsedDevice>
 */
class UsedDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cas_id' => strtoupper(fake()->unique()->bothify(str_repeat('?', 32))),
            'etag' => fake()->sha1(),
            'name' => fake()->bothify('DC-##'),
            'manufacturer' => 'Mindray Co. Ltd.',
            'description' => 'Farbdopplersystem',
            'year' => fake()->numberBetween(2008, 2024),
            'probes' => [],
            'images' => [],
            'synced_at' => now(),
        ];
    }
}
