<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\LeadActivity>
 */
class LeadActivityFactory extends Factory
{
    protected $model = LeadActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'type' => $this->faker->randomElement(['note', 'email_sent', 'call', 'meeting', 'follow_up']),
            'content' => $this->faker->sentence(),
            'scheduled_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the activity is a note.
     */
    public function note(): self
    {
        return $this->state(fn (): array => [
            'type' => 'note',
        ]);
    }

    /**
     * Indicate that the activity is a call.
     */
    public function call(): self
    {
        return $this->state(fn (): array => [
            'type' => 'call',
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the activity is an email.
     */
    public function email(): self
    {
        return $this->state(fn (): array => [
            'type' => 'email_sent',
        ]);
    }

    /**
     * Indicate that the activity is a meeting.
     */
    public function meeting(): self
    {
        return $this->state(fn (): array => [
            'type' => 'meeting',
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the activity is a follow-up.
     */
    public function followUp(): self
    {
        return $this->state(fn (): array => [
            'type' => 'follow_up',
            'scheduled_at' => now()->addDays(7),
        ]);
    }
}
