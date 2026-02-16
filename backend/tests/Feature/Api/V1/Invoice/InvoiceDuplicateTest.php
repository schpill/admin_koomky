<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('duplicating invoice creates draft clone with new number and same line items', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'number' => 'FAC-'.date('Y').'-0001',
    ]);

    LineItem::factory()->count(2)->create([
        'documentable_type' => Invoice::class,
        'documentable_id' => $invoice->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/duplicate');

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'draft');

    $cloneId = (string) $response->json('data.id');

    expect($cloneId)->not->toBe($invoice->id);
    expect($response->json('data.number'))->not->toBe($invoice->number);

    $this->assertDatabaseCount('line_items', 4);
});
