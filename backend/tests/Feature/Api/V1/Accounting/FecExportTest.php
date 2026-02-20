<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create([
        'base_currency' => 'EUR',
        'accounting_journal_sales' => 'VTE',
    ]);
    $this->actingAs($this->user, 'sanctum');
});

test('can get fec export entry count', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 100.00,
        'tax_amount' => 20.00,
    ]);

    $response = $this->getJson('/api/v1/accounting/fec/count?date_from=2024-01-01&date_to=2024-12-31');

    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'data' => ['entry_count']]);
});

test('can export fec file', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);

    \App\Models\Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => '2024-06-15',
        'total' => 100.00,
    ]);

    $response = $this->getJson('/api/v1/accounting/fec?date_from=2024-01-01&date_to=2024-12-31');

    $response->assertStatus(200);
});

test('unauthorized access is rejected', function () {
    // Clear the authenticated user
    auth()->forgetUser();

    $response = $this->getJson('/api/v1/accounting/fec/count');

    $response->assertStatus(401);
});
