<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'task_id' => Task::factory(),
            'duration_minutes' => $this->faker->numberBetween(15, 240),
            'date' => $this->faker->dateTimeBetween('-15 days', 'now'),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
