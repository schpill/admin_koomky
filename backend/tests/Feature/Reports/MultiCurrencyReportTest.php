<?php

use App\Models\Client;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('revenue report aggregates across currencies in base currency and exposes breakdown', function () {
    $user = User::factory()->create(['base_currency' => 'EUR']);
    $client = Client::factory()->create(['user_id' => $user->id]);

    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    Currency::factory()->create(['code' => 'GBP', 'is_active' => true]);

    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'target_currency' => 'EUR',
        'rate' => 0.9,
        'fetched_at' => '2026-02-17 09:00:00',
        'source' => 'open_exchange_rates',
    ]);

    ExchangeRate::factory()->create([
        'base_currency' => 'GBP',
        'target_currency' => 'EUR',
        'rate' => 1.2,
        'fetched_at' => '2026-02-17 09:00:00',
        'source' => 'open_exchange_rates',
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'issue_date' => '2026-02-17',
        'currency' => 'USD',
        'total' => 100,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'issue_date' => '2026-02-17',
        'currency' => 'GBP',
        'total' => 100,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/revenue?date_from=2026-01-01&date_to=2026-12-31');

    $response->assertStatus(200)
        ->assertJsonPath('data.base_currency', 'EUR')
        ->assertJsonPath('data.total_revenue', 210.0)
        ->assertJsonPath('data.currency_breakdown.USD', 100.0)
        ->assertJsonPath('data.currency_breakdown.GBP', 100.0);
});
