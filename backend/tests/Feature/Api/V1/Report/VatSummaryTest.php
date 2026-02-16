<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('vat summary aggregates vat by rates', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'issue_date' => '2026-02-12',
        'total' => 240,
    ]);

    LineItem::factory()->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
        'quantity' => 1,
        'unit_price' => 100,
        'vat_rate' => 20,
    ]);

    LineItem::factory()->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
        'quantity' => 1,
        'unit_price' => 100,
        'vat_rate' => 10,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/vat-summary?date_from=2026-01-01&date_to=2026-12-31');

    $response->assertStatus(200)
        ->assertJsonPath('data.total_vat', 30.0)
        ->assertJsonCount(2, 'data.by_rate');

    $rates = collect($response->json('data.by_rate'))->pluck('rate')->all();
    expect($rates)->toContain('20');
    expect($rates)->toContain('10');
});
