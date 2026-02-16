<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CreditNote>
 */
class CreditNoteFactory extends Factory
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
            'invoice_id' => Invoice::factory(),
            'number' => 'AVO-'.date('Y').'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => $this->faker->randomElement([
                'draft',
                'sent',
                'applied',
            ]),
            'issue_date' => $issueDate,
            'subtotal' => 100,
            'tax_amount' => 20,
            'total' => 120,
            'currency' => 'EUR',
            'reason' => $this->faker->optional()->sentence(),
            'pdf_path' => null,
            'sent_at' => null,
            'applied_at' => null,
        ];
    }
}
