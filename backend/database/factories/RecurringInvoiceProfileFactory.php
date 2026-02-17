<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\RecurringInvoiceProfile>
 */
class RecurringInvoiceProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-10 days', '+10 days');

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'name' => $this->faker->words(3, true),
            'frequency' => $this->faker->randomElement([
                'weekly',
                'biweekly',
                'monthly',
                'quarterly',
                'semiannual',
                'annual',
            ]),
            'start_date' => $startDate,
            'end_date' => null,
            'next_due_date' => $startDate,
            'day_of_month' => null,
            'line_items' => [
                [
                    'description' => 'Recurring service',
                    'quantity' => 1,
                    'unit_price' => 250,
                    'vat_rate' => 20,
                ],
            ],
            'notes' => $this->faker->optional()->sentence(),
            'payment_terms_days' => 30,
            'tax_rate' => 20,
            'discount_percent' => null,
            'status' => 'active',
            'last_generated_at' => null,
            'occurrences_generated' => 0,
            'max_occurrences' => null,
            'auto_send' => false,
            'currency' => 'EUR',
        ];
    }
}
