<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
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
            'number' => 'FAC-'.date('Y').'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => $this->faker->randomElement([
                'draft',
                'sent',
                'viewed',
                'partially_paid',
                'paid',
                'overdue',
                'cancelled',
            ]),
            'issue_date' => $issueDate,
            'due_date' => (clone $issueDate)->modify('+30 days'),
            'subtotal' => 1000,
            'tax_amount' => 200,
            'discount_type' => null,
            'discount_value' => null,
            'discount_amount' => 0,
            'total' => 1200,
            'currency' => 'EUR',
            'notes' => $this->faker->optional()->sentence(),
            'payment_terms' => '30 days',
            'pdf_path' => null,
            'sent_at' => null,
            'viewed_at' => null,
            'paid_at' => null,
        ];
    }
}
