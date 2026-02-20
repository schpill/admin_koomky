<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => $this->faker->company(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'source' => $this->faker->randomElement(['manual', 'referral', 'website', 'campaign', 'other']),
            'status' => $this->faker->randomElement(['new', 'contacted', 'qualified', 'proposal_sent', 'negotiating']),
            'estimated_value' => $this->faker->randomFloat(2, 1000, 50000),
            'currency' => 'EUR',
            'probability' => $this->faker->numberBetween(10, 90),
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'notes' => $this->faker->sentence(),
            'lost_reason' => null,
            'won_client_id' => null,
            'converted_at' => null,
            'pipeline_position' => 0,
        ];
    }

    /**
     * Indicate that the lead is won.
     */
    public function won(): self
    {
        return $this->state(fn (): array => [
            'status' => 'won',
            'probability' => 100,
            'converted_at' => now(),
        ]);
    }

    /**
     * Indicate that the lead is lost.
     */
    public function lost(): self
    {
        return $this->state(fn (): array => [
            'status' => 'lost',
            'probability' => 0,
            'lost_reason' => 'Budget constraints',
        ]);
    }

    /**
     * Indicate that the lead is new.
     */
    public function newLead(): self
    {
        return $this->state(fn (): array => [
            'status' => 'new',
            'probability' => 20,
        ]);
    }

    /**
     * Set a specific status for the lead.
     */
    public function withStatus(string $status): self
    {
        return $this->state(fn (): array => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that the lead is converted to a client.
     */
    public function converted(): self
    {
        return $this->state(fn (): array => [
            'status' => 'won',
            'probability' => 100,
            'converted_at' => now(),
            'won_client_id' => Client::factory(),
        ]);
    }
}
