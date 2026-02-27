<?php

namespace Database\Factories;

use App\Models\ReminderSequence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReminderSequence>
 */
class ReminderSequenceFactory extends Factory
{
    protected $model = ReminderSequence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }

    public function withSteps(int $count = 3): static
    {
        return $this->afterCreating(function (ReminderSequence $sequence) use ($count): void {
            for ($i = 1; $i <= $count; $i++) {
                $sequence->steps()->create([
                    'step_number' => $i,
                    'delay_days' => [3, 7, 14][$i - 1] ?? ($i * 7),
                    'subject' => "Relance étape {$i}",
                    'body' => 'Bonjour {{client_name}}, facture {{invoice_number}} en attente.',
                ]);
            }
        });
    }
}
