<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\PortalSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function webhookSignature(array $payload, string $secret): array
{
    $json = json_encode($payload, JSON_THROW_ON_ERROR);

    return [
        'Stripe-Signature' => hash_hmac('sha256', $json, $secret),
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];
}

test('payment_intent.succeeded webhook marks payment and invoice as paid', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'stripe_webhook_secret' => 'whsec_test',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 120,
    ]);

    $paymentIntent = PaymentIntent::factory()->create([
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'stripe_payment_intent_id' => 'pi_success_123',
        'amount' => 120,
        'status' => 'processing',
    ]);

    $payload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_success_123',
                'amount_received' => 12000,
                'currency' => 'eur',
            ],
        ],
    ];

    $this->postJson('/api/v1/webhooks/stripe', $payload, webhookSignature($payload, 'whsec_test'))
        ->assertStatus(200);

    $paymentIntent->refresh();
    $invoice->refresh();

    expect($paymentIntent->status)->toBe('succeeded');
    expect($invoice->status)->toBe('paid');
    $this->assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'amount' => 120,
    ]);
});

test('payment_intent.payment_failed webhook updates failure status', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'stripe_webhook_secret' => 'whsec_test',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 90,
    ]);

    $paymentIntent = PaymentIntent::factory()->create([
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'stripe_payment_intent_id' => 'pi_failed_123',
        'amount' => 90,
        'status' => 'processing',
    ]);

    $payload = [
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_failed_123',
                'last_payment_error' => [
                    'message' => 'Card declined',
                ],
            ],
        ],
    ];

    $this->postJson('/api/v1/webhooks/stripe', $payload, webhookSignature($payload, 'whsec_test'))
        ->assertStatus(200);

    $paymentIntent->refresh();
    expect($paymentIntent->status)->toBe('failed');
    expect($paymentIntent->failure_reason)->toBe('Card declined');
});

test('charge.refunded webhook marks payment intent refunded', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'stripe_webhook_secret' => 'whsec_test',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'total' => 150,
    ]);

    $paymentIntent = PaymentIntent::factory()->create([
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'stripe_payment_intent_id' => 'pi_refunded_123',
        'amount' => 150,
        'status' => 'succeeded',
    ]);

    $payload = [
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'payment_intent' => 'pi_refunded_123',
                'amount_refunded' => 15000,
            ],
        ],
    ];

    $this->postJson('/api/v1/webhooks/stripe', $payload, webhookSignature($payload, 'whsec_test'))
        ->assertStatus(200);

    $paymentIntent->refresh();
    expect($paymentIntent->status)->toBe('refunded');
});

test('stripe webhook with invalid signature is rejected', function () {
    $payload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_any',
            ],
        ],
    ];

    $this->postJson('/api/v1/webhooks/stripe', $payload, [
        'Stripe-Signature' => 'invalid-signature',
    ])->assertStatus(400);
});
