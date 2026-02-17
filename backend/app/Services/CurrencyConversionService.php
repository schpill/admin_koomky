<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use RuntimeException;

class CurrencyConversionService
{
    public function convert(float $amount, string $fromCurrency, string $toCurrency, ?Carbon $date = null): float
    {
        if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
            return round($amount, 2);
        }

        $rate = $this->rateFor($fromCurrency, $toCurrency, $date);

        return round($amount * $rate, 2);
    }

    public function rateFor(string $fromCurrency, string $toCurrency, ?Carbon $date = null): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $directRate = $this->findRate($fromCurrency, $toCurrency, $date);
        if ($directRate !== null) {
            return $directRate;
        }

        $inverseRate = $this->findRate($toCurrency, $fromCurrency, $date);
        if ($inverseRate !== null && $inverseRate > 0) {
            return (float) round(1 / $inverseRate, 6);
        }

        throw new RuntimeException("Missing exchange rate for {$fromCurrency}->{$toCurrency}");
    }

    private function findRate(string $baseCurrency, string $targetCurrency, ?Carbon $date = null): ?float
    {
        $query = ExchangeRate::query()
            ->where('base_currency', $baseCurrency)
            ->where('target_currency', $targetCurrency);

        if ($date !== null) {
            $query->where('fetched_at', '<=', $date->copy()->endOfDay());
        }

        $rate = $query->orderByDesc('fetched_at')->value('rate');

        return is_numeric($rate) ? (float) $rate : null;
    }
}
