<?php

namespace App\Services\ExchangeRates;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenExchangeRatesDriver implements ExchangeRateDriver
{
    /**
     * @return array<string, float>
     */
    public function fetchRates(string $baseCurrency): array
    {
        $response = Http::timeout(15)->get('https://openexchangerates.org/api/latest.json', [
            'app_id' => (string) config('services.open_exchange_rates.app_id', ''),
            'base' => strtoupper($baseCurrency),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch exchange rates from OpenExchangeRates');
        }

        $rates = $response->json('rates');
        if (! is_array($rates)) {
            throw new RuntimeException('OpenExchangeRates response has no rates payload');
        }

        $normalized = [];
        foreach ($rates as $code => $rate) {
            if (is_numeric($rate)) {
                $normalized[strtoupper((string) $code)] = (float) $rate;
            }
        }

        return $normalized;
    }

    public function source(): string
    {
        return 'open_exchange_rates';
    }
}
