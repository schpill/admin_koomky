<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProjectTemplate>
 */
class ProjectTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'billing_type' => $this->faker->randomElement(['hourly', 'fixed']),
            'default_hourly_rate' => $this->faker->randomFloat(2, 30, 300),
            'default_currency' => 'EUR',
            'estimated_hours' => $this->faker->optional()->randomFloat(2, 1, 120),
            'is_public' => false,
        ];
    }

    /**
     * Indicate that the template has tasks.
     *
     * @return static
     */
    public function withTasks(int $count = 3)
    {
        return $this->has(
            \Database\Factories\ProjectTemplateTaskFactory::new()->count($count),
            'templateTasks'
        );
    }
}
