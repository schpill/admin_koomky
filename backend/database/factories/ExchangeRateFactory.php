<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fetchedAt = now()->subHours(random_int(1, 48));

        return [
            'base_currency' => 'EUR',
            'target_currency' => 'USD',
            'rate' => round(random_int(10, 250) / 100, 6),
            'fetched_at' => $fetchedAt,
            'rate_date' => $fetchedAt->format('Y-m-d'),
            'source' => 'open_exchange_rates',
        ];
    }
}
