<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Division>
 */
class DivisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Human Resources', 'Engineering', 'Marketing', 'Sales', 'Finance', 'Operations', 'IT Support']),
            'description' => $this->faker->sentence(8),
            'company_id' => \App\Models\Company::factory(),
            'is_active' => $this->faker->boolean(90)
        ];
    }
}
