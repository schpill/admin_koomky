<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalPaymentHeaders(Client $client): array
{
    $token = PortalAccessToken::factory()->create([
        'client_id' => $client->id,
        'email' => $client->email,
        'is_active' => true,
        'expires_at' => now()->addHour(),
    ]);

    $verifyResponse = test()->getJson('/api/v1/portal/auth/verify/'.$token->token);
    $verifyResponse->assertStatus(200);

    return [
        'Authorization' => 'Bearer '.$verifyResponse->json('data.portal_token'),
    ];
}

test('portal client can create payment intent for an unpaid invoice', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
        'payment_enabled' => true,
        'stripe_publishable_key' => 'pk_test',
        'stripe_secret_key' => 'sk_test',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 140,
    ]);

    $response = $this->postJson('/api/v1/portal/invoices/'.$invoice->id.'/pay', [], portalPaymentHeaders($client));

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.invoice_id', $invoice->id)
        ->assertJsonPath('data.status', 'processing')
        ->assertJsonPath('data.client_secret', fn ($value): bool => is_string($value) && $value !== '');

    $this->assertDatabaseHas('payment_intents', [
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'status' => 'processing',
    ]);
});

test('portal client can check payment status', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
        'payment_enabled' => true,
        'stripe_publishable_key' => 'pk_test',
        'stripe_secret_key' => 'sk_test',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 80,
    ]);

    $this->postJson('/api/v1/portal/invoices/'.$invoice->id.'/pay', [], portalPaymentHeaders($client))
        ->assertStatus(201);

    $this->getJson('/api/v1/portal/invoices/'.$invoice->id.'/payment-status', portalPaymentHeaders($client))
        ->assertStatus(200)
        ->assertJsonPath('data.invoice_id', $invoice->id);
});

test('portal client cannot pay an already paid invoice', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
        'payment_enabled' => true,
        'stripe_publishable_key' => 'pk_test',
        'stripe_secret_key' => 'sk_test',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'total' => 80,
        'paid_at' => now(),
    ]);

    $this->postJson('/api/v1/portal/invoices/'.$invoice->id.'/pay', [], portalPaymentHeaders($client))
        ->assertStatus(422);
});

test('portal client cannot pay another client invoice', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);
    $otherClient = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
        'payment_enabled' => true,
        'stripe_publishable_key' => 'pk_test',
        'stripe_secret_key' => 'sk_test',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $otherClient->id,
        'status' => 'sent',
    ]);

    $this->postJson('/api/v1/portal/invoices/'.$invoice->id.'/pay', [], portalPaymentHeaders($client))
        ->assertStatus(404);
});
