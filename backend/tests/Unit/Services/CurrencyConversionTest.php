<?php

use App\Models\ExchangeRate;
use App\Services\CurrencyConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('currency conversion supports direct inverse same-currency and date-specific conversions', function () {
    ExchangeRate::factory()->create([
        'base_currency' => 'EUR',
        'target_currency' => 'USD',
        'rate' => 1.2,
        'fetched_at' => '2026-02-01 08:00:00',
        'rate_date' => '2026-02-01',
        'source' => 'open_exchange_rates',
    ]);

    ExchangeRate::factory()->create([
        'base_currency' => 'EUR',
        'target_currency' => 'USD',
        'rate' => 1.1,
        'fetched_at' => '2026-01-20 08:00:00',
        'rate_date' => '2026-01-20',
        'source' => 'open_exchange_rates',
    ]);

    $service = app(CurrencyConversionService::class);

    expect($service->convert(100, 'EUR', 'USD'))->toBe(120.0);
    expect($service->convert(120, 'USD', 'EUR'))->toBe(100.0);
    expect($service->convert(42, 'EUR', 'EUR'))->toBe(42.0);
    expect($service->convert(100, 'EUR', 'USD', Carbon::parse('2026-01-21')))->toBe(110.0);
});

test('currency conversion throws when rate is missing', function () {
    $service = app(CurrencyConversionService::class);

    expect(fn () => $service->convert(100, 'JPY', 'CHF'))->toThrow(RuntimeException::class);
});
