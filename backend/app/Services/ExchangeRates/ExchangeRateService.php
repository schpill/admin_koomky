<?php

namespace App\Services\ExchangeRates;

use Illuminate\Support\Carbon;

interface ExchangeRateService
{
    public function fetchAndStore(string $baseCurrency): int;

    /**
     * @return array<string, float>
     */
    public function latestRates(string $baseCurrency, ?Carbon $asOf = null): array;
}
