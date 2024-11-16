<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Service '.$this->faker->name(),
            'environment_id' => \App\Models\Environment::factory(),
            'duration' => 15,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'booking_window_lead' => 0,
            'booking_window_end' => 0,
            'cancellation_window_end' => 0,
            'slots' => 1,
        ];
    }
}
