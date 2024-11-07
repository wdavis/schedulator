<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Resource;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'resource_id' => Resource::factory(),
            'location_id' => Location::factory(),
            'service_id' => Service::factory(),
            'name' => $this->faker->name(),
            'starts_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'ends_at' => $this->faker->dateTimeBetween('+1 week', '+2 week'),
            'meta' => []
        ];
    }
}
