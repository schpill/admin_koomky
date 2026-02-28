<?php

namespace Database\Factories;

use App\Models\ProjectTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProjectTemplateTask>
 */
class ProjectTemplateTaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => ProjectTemplate::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'estimated_hours' => $this->faker->optional()->randomFloat(2, 1, 40),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'sort_order' => 0,
        ];
    }

    /**
     * Configure the factory to auto-increment sort_order.
     */
    public function configure(): static
    {
        return $this->sequence(
            fn ($sequence) => ['sort_order' => $sequence->index]
        );
    }
}
