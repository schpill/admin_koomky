<?php

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('currency endpoints list active currencies and latest rates', function () {
    $user = User::factory()->create();

    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    Currency::factory()->create(['code' => 'JPY', 'is_active' => false]);

    ExchangeRate::factory()->create([
        'base_currency' => 'EUR',
        'target_currency' => 'USD',
        'rate' => 1.1,
        'fetched_at' => now()->subDay(),
        'rate_date' => now()->subDay()->toDateString(),
        'source' => 'open_exchange_rates',
    ]);

    ExchangeRate::factory()->create([
        'base_currency' => 'EUR',
        'target_currency' => 'USD',
        'rate' => 1.2,
        'fetched_at' => now(),
        'rate_date' => now()->toDateString(),
        'source' => 'open_exchange_rates',
    ]);

    $currencies = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/currencies');

    $currencies->assertStatus(200)
        ->assertJsonPath('data.0.code', 'EUR')
        ->assertJsonMissing(['code' => 'JPY']);

    $rates = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/currencies/rates?base=EUR');

    $rates->assertStatus(200)
        ->assertJsonPath('data.base_currency', 'EUR')
        ->assertJsonPath('data.rates.USD', 1.2);
});
