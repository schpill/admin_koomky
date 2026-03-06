<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\DripSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

class DripEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sequence_id' => DripSequence::factory(),
            'contact_id' => Contact::factory(),
            'current_step_position' => 0,
            'status' => 'active',
            'enrolled_at' => now(),
            'last_processed_at' => null,
            'completed_at' => null,
        ];
    }
}
