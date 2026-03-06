<?php

namespace Database\Factories;

use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'type' => 'end',
            'config' => [],
            'next_step_id' => null,
            'else_step_id' => null,
            'position_x' => 0,
            'position_y' => 0,
        ];
    }
}
