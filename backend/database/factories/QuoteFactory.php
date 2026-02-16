<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'project_id' => null,
            'converted_invoice_id' => null,
            'number' => 'DEV-'.date('Y').'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => $this->faker->randomElement([
                'draft',
                'sent',
                'accepted',
                'rejected',
                'expired',
            ]),
            'issue_date' => $issueDate,
            'valid_until' => (clone $issueDate)->modify('+30 days'),
            'subtotal' => 1000,
            'tax_amount' => 200,
            'discount_type' => null,
            'discount_value' => null,
            'discount_amount' => 0,
            'total' => 1200,
            'currency' => 'EUR',
            'notes' => $this->faker->optional()->sentence(),
            'pdf_path' => null,
            'sent_at' => null,
            'accepted_at' => null,
        ];
    }
}
