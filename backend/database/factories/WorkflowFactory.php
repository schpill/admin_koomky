<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'trigger_type' => 'manual',
            'trigger_config' => [],
            'status' => 'draft',
            'entry_step_id' => null,
        ];
    }
}
