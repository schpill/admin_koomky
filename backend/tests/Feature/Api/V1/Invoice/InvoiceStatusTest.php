<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('send endpoint changes draft invoice status to sent', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/send');

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'sent');
});

test('invalid status transition is rejected', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'cancelled',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/send');

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Invalid invoice status transition');
});
