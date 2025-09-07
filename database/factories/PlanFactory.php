<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Basic Plan', 'Premium Plan', 'Enterprise Plan', 'Starter Plan']),
            'description' => $this->faker->sentence(10),
            'employee_limit' => $this->faker->randomElement([10, 50, 100, 500, 1000]),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'is_active' => $this->faker->boolean(90)
        ];
    }
}
