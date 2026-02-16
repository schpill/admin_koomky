<?php

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('apply endpoint updates invoice and credit note status', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 500,
    ]);

    $creditNote = CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'sent',
        'total' => 200,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credit-notes/'.$creditNote->id.'/apply');

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'applied');

    $invoice->refresh();

    expect((float) $invoice->amount_paid)->toBe(200.0);
    expect($invoice->status)->toBe('partially_paid');
});

test('cannot apply a credit note twice', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 500,
    ]);

    $creditNote = CreditNote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_id' => $invoice->id,
        'status' => 'applied',
        'applied_at' => now(),
        'total' => 50,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credit-notes/'.$creditNote->id.'/apply');

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Credit note has already been applied');
});
