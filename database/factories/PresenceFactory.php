<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Presence>
 */
class PresenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement(['checkin', 'checkout']),
            'presence_type' => $this->faker->randomElement(['rfid', 'face_recognition', 'fingerprint', 'manual']),
            'data' => $this->faker->optional()->word(),
            'address' => $this->faker->optional()->address(),
            'latitude' => $this->faker->latitude(-90, 90),
            'longitude' => $this->faker->longitude(-180, 180),
            'presence_time' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'is_valid' => $this->faker->boolean(90),
            'notes' => $this->faker->optional()->sentence()
        ];
    }
}
