<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PaymentIntent>
 */
class PaymentIntentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'client_id' => Client::factory(),
            'stripe_payment_intent_id' => 'pi_'.$this->faker->unique()->bothify('##########'),
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'currency' => 'EUR',
            'status' => $this->faker->randomElement([
                'pending',
                'processing',
                'succeeded',
                'failed',
                'refunded',
            ]),
            'payment_method' => 'card',
            'failure_reason' => null,
            'paid_at' => null,
            'refunded_at' => null,
            'metadata' => null,
        ];
    }
}
