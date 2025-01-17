<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduleOverride>
 */
class ScheduleOverrideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resource_id' => \App\Models\Resource::factory(),
            'location_id' => \App\Models\Location::factory(),
            'starts_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'ends_at' => $this->faker->dateTimeBetween('+1 week', '+2 week'),
            'type' => 'block',
            //
        ];
    }
}
