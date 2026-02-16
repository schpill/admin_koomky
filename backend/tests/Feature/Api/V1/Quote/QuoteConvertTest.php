<?php

use App\Models\Client;
use App\Models\LineItem;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('convert endpoint creates invoice and links quote', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    LineItem::factory()->create([
        'documentable_type' => Quote::class,
        'documentable_id' => $quote->id,
        'description' => 'Consulting block',
        'quantity' => 3,
        'unit_price' => 120,
        'vat_rate' => 20,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/quotes/'.$quote->id.'/convert');

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.client_id', $client->id);

    $quote->refresh();

    expect($quote->converted_invoice_id)->not()->toBeNull();
    expect($quote->status)->toBe('accepted');
    $this->assertDatabaseHas('line_items', [
        'documentable_type' => App\Models\Invoice::class,
        'documentable_id' => $quote->converted_invoice_id,
        'description' => 'Consulting block',
    ]);
});
