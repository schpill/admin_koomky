<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('recording full payment marks invoice as paid', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 1000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/payments', [
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'paid');
});

test('recording partial payment marks invoice as partially_paid', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 1000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/payments', [
            'amount' => 250,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'card',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'partially_paid')
        ->assertJsonPath('data.amount_paid', 250.0);
});

test('overpayment is rejected', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 1000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/payments', [
            'amount' => 1200,
            'payment_date' => now()->toDateString(),
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Payment amount exceeds invoice balance');
});
