<?php

namespace Database\Factories;

use App\Models\ReminderSequence;
use App\Models\ReminderStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReminderStep>
 */
class ReminderStepFactory extends Factory
{
    protected $model = ReminderStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $number = 0;
        $number++;

        return [
            'sequence_id' => ReminderSequence::factory(),
            'step_number' => $number,
            'delay_days' => [3, 7, 14][($number - 1) % 3],
            'subject' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
        ];
    }
}
