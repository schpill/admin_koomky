<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement(['todo', 'in_progress', 'in_review', 'done', 'blocked']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'estimated_hours' => $this->faker->optional()->randomFloat(2, 0.5, 16),
            'due_date' => $this->faker->optional()->dateTimeBetween('-5 days', '+20 days'),
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }
}
