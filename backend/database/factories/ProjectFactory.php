<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'reference' => 'PRJ-'.date('Y').'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement(['draft', 'proposal_sent', 'in_progress', 'on_hold']),
            'billing_type' => $this->faker->randomElement(['hourly', 'fixed']),
            'currency' => 'EUR',
            'hourly_rate' => $this->faker->randomFloat(2, 30, 300),
            'fixed_price' => $this->faker->randomFloat(2, 1000, 30000),
            'estimated_hours' => $this->faker->optional()->randomFloat(2, 1, 120),
            'start_date' => $this->faker->optional()->dateTimeBetween('-3 months', 'now'),
            'deadline' => $this->faker->optional()->dateTimeBetween('now', '+3 months'),
        ];
    }
}
