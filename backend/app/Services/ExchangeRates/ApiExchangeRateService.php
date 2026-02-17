<?php

namespace App\Services\ExchangeRates;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApiExchangeRateService implements ExchangeRateService
{
    public function __construct(private readonly ExchangeRateDriver $driver) {}

    public function fetchAndStore(string $baseCurrency): int
    {
        $baseCurrency = strtoupper($baseCurrency);

        try {
            $rates = $this->driver->fetchRates($baseCurrency);
        } catch (Throwable $exception) {
            Log::warning('exchange_rate_fetch_failed', [
                'base_currency' => $baseCurrency,
                'source' => $this->driver->source(),
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }

        $activeTargets = Currency::query()
            ->active()
            ->where('code', '!=', $baseCurrency)
            ->pluck('code')
            ->map(fn (string $code): string => strtoupper($code));

        $now = now();
        $stored = 0;

        foreach ($activeTargets as $targetCurrency) {
            $rate = $rates[$targetCurrency] ?? null;
            if (! is_numeric($rate) || (float) $rate <= 0) {
                continue;
            }

            ExchangeRate::query()->updateOrCreate(
                [
                    'base_currency' => $baseCurrency,
                    'target_currency' => $targetCurrency,
                    'rate_date' => $now->toDateString(),
                ],
                [
                    'rate' => (float) $rate,
                    'fetched_at' => $now,
                    'source' => $this->driver->source(),
                ]
            );

            $stored++;
        }

        return $stored;
    }

    /**
     * @return array<string, float>
     */
    public function latestRates(string $baseCurrency, ?Carbon $asOf = null): array
    {
        $baseCurrency = strtoupper($baseCurrency);

        $query = ExchangeRate::query()->where('base_currency', $baseCurrency);

        if ($asOf !== null) {
            $query->where('fetched_at', '<=', $asOf->copy()->endOfDay());
        }

        $rows = $query
            ->orderByDesc('fetched_at')
            ->get(['target_currency', 'rate']);

        $rates = [];
        foreach ($rows as $row) {
            if (! array_key_exists($row->target_currency, $rates)) {
                $rates[$row->target_currency] = (float) $row->rate;
            }
        }

        return $rates;
    }
}
