<?php

use App\Models\Currency;
use App\Services\ExchangeRates\ApiExchangeRateService;
use App\Services\ExchangeRates\EcbExchangeRatesDriver;
use App\Services\ExchangeRates\ExchangeRateDriver;
use App\Services\ExchangeRates\OpenExchangeRatesDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('open exchange rates driver fetches and service stores rates', function () {
    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    Currency::factory()->create(['code' => 'GBP', 'is_active' => true]);

    Http::fake([
        'https://openexchangerates.org/*' => Http::response([
            'base' => 'EUR',
            'rates' => [
                'USD' => 1.1,
                'GBP' => 0.85,
            ],
        ], 200),
    ]);

    $driver = new OpenExchangeRatesDriver();
    $service = new ApiExchangeRateService($driver);

    $stored = $service->fetchAndStore('EUR');

    expect($stored)->toBe(2);
    $this->assertDatabaseHas('exchange_rates', [
        'base_currency' => 'EUR',
        'target_currency' => 'USD',
        'source' => 'open_exchange_rates',
    ]);
});

test('ecb driver fetches and stores rates', function () {
    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
  <Cube>
    <Cube time="2026-02-17">
      <Cube currency="USD" rate="1.10"/>
    </Cube>
  </Cube>
</gesmes:Envelope>
XML;

    Http::fake([
        'https://www.ecb.europa.eu/*' => Http::response($xml, 200),
        '*' => Http::response('', 500),
    ]);

    $driver = new EcbExchangeRatesDriver();
    $service = new ApiExchangeRateService($driver);

    $stored = $service->fetchAndStore('EUR');
    expect($stored)->toBe(1);

    $this->assertDatabaseHas('exchange_rates', [
        'base_currency' => 'EUR',
        'target_currency' => 'USD',
        'source' => 'ecb',
    ]);
});

test('exchange rate service handles driver failures gracefully', function () {
    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

    $failingDriver = new class implements ExchangeRateDriver {
        public function fetchRates(string $baseCurrency): array
        {
            throw new RuntimeException('Simulated provider failure');
        }

        public function source(): string
        {
            return 'failing_provider';
        }
    };

    $service = new ApiExchangeRateService($failingDriver);

    expect($service->fetchAndStore('EUR'))->toBe(0);
});
