<?php

namespace Database\Factories;

use App\Models\DripSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

class DripStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sequence_id' => DripSequence::factory(),
            'position' => 1,
            'delay_hours' => 0,
            'condition' => 'none',
            'subject' => $this->faker->sentence(3),
            'content' => '<p>'.$this->faker->sentence(6).'</p>',
            'template_id' => null,
        ];
    }
}
