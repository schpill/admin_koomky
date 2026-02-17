<?php

use App\Models\Client;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function multiCurrencyInvoicePayload(string $clientId): array
{
    return [
        'client_id' => $clientId,
        'issue_date' => '2026-02-17',
        'due_date' => '2026-03-19',
        'currency' => 'USD',
        'line_items' => [
            [
                'description' => 'International service',
                'quantity' => 1,
                'unit_price' => 100,
                'vat_rate' => 0,
            ],
        ],
    ];
}

test('invoice creation computes base currency totals', function () {
    $user = User::factory()->create(['base_currency' => 'EUR']);
    $client = Client::factory()->create(['user_id' => $user->id]);

    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'target_currency' => 'EUR',
        'rate' => 0.9,
        'fetched_at' => '2026-02-17 09:00:00',
        'source' => 'open_exchange_rates',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices', multiCurrencyInvoicePayload($client->id));

    $response->assertStatus(201)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.base_currency', 'EUR')
        ->assertJsonPath('data.base_currency_total', 90.0);
});
