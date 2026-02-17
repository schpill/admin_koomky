<?php

namespace App\Services\ExchangeRates;

interface ExchangeRateDriver
{
    /**
     * @return array<string, float>
     */
    public function fetchRates(string $baseCurrency): array;

    public function source(): string;
}
