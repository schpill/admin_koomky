<?php

namespace App\Console\Commands;

use App\Services\ExchangeRates\ExchangeRateService;
use Illuminate\Console\Command;

class FetchExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:fetch {--base=EUR}';

    protected $description = 'Fetch and store exchange rates for active currencies';

    public function handle(ExchangeRateService $exchangeRateService): int
    {
        $baseCurrency = strtoupper((string) $this->option('base'));
        $stored = $exchangeRateService->fetchAndStore($baseCurrency);

        $this->info("Exchange rates stored: {$stored}");

        return self::SUCCESS;
    }
}
