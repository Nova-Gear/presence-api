<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ManualPresenceRequest>
 */
class ManualPresenceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-30 days', '+30 days');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s') . ' +7 days');

        return [
            'user_id' => \App\Models\User::factory(),
            'request_type' => $this->faker->randomElement(['sick', 'leave', 'vacation', 'business_trip', 'other']),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'reason' => $this->faker->sentence(),
            'attachment_path' => $this->faker->optional()->word() . '.pdf',
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'approved_by' => $this->faker->boolean(60) ? \App\Models\User::factory() : null,
            'approved_at' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'approval_notes' => $this->faker->optional()->sentence()
        ];
    }
}
