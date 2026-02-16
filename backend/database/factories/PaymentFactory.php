<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 1000),
            'payment_date' => $this->faker->dateTimeBetween('-15 days', 'now'),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'card', 'cash']),
            'reference' => $this->faker->optional()->bothify('PAY-#######'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
