<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'contact_id' => Contact::factory(),
            'current_step_id' => null,
            'status' => 'active',
            'enrolled_at' => now(),
            'last_processed_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ];
    }
}
