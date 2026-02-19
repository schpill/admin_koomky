<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

const WEBHOOK_SECRET = 'whsec_test_secret';

beforeEach(function () {
    Notification::fake();
    Config::set('services.stripe.webhook_secret', WEBHOOK_SECRET);

    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    $this->invoice = Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'sent',
        'total' => 120.00,
    ]);
    $this->paymentIntent = PaymentIntent::factory()->create([
        'invoice_id' => $this->invoice->id,
        'client_id' => $this->client->id,
        'stripe_payment_intent_id' => 'pi_success_123',
        'amount' => 120.00,
        'status' => 'processing',
    ]);
});

function postWebhook(array $data, ?string $signature = null): \Illuminate\Testing\TestResponse
{
    $timestamp = time();
    $payload = json_encode($data, JSON_UNESCAPED_SLASHES);

    if (! $signature) {
        $stringToSign = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $stringToSign, WEBHOOK_SECRET);
    }

    $headers = ['Stripe-Signature' => "t={$timestamp},v1={$signature}"];

    return test()->postJson('/api/v1/webhooks/stripe', $data, $headers);
}

test('payment_intent.succeeded webhook creates payment with correct converted amount', function () {
    postWebhook([
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id' => 'pi_success_123',
            'object' => 'payment_intent',
            'amount_received' => 12000, // 120.00 EUR in cents
            'currency' => 'eur',
        ]],
    ])->assertStatus(200);

    $this->paymentIntent->refresh();
    $this->invoice->refresh();

    expect($this->paymentIntent->status)->toBe('succeeded');
    expect($this->invoice->status)->toBe('paid');
    $this->assertDatabaseHas('payments', ['invoice_id' => $this->invoice->id, 'amount' => 120.00]);
});

test('idempotency: receiving the same succeeded webhook twice creates only one payment', function () {
    $payload = [
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id' => 'pi_success_123',
            'object' => 'payment_intent',
            'amount_received' => 12000,
            'currency' => 'eur',
        ]],
    ];

    postWebhook($payload)->assertOk();
    expect(Payment::where('invoice_id', $this->invoice->id)->count())->toBe(1);

    postWebhook($payload)->assertOk()->assertJsonPath('message', 'Event already handled');
    expect(Payment::where('invoice_id', $this->invoice->id)->count())->toBe(1);
});

test('webhook handles partial payment correctly', function () {
    postWebhook([
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id' => 'pi_success_123',
            'object' => 'payment_intent',
            'amount_received' => 5000, // 50.00 EUR
            'currency' => 'eur',
        ]],
    ])->assertOk();

    $this->invoice->refresh();
    expect($this->invoice->status)->toBe('partially_paid');
    $this->assertDatabaseHas('payments', ['invoice_id' => $this->invoice->id, 'amount' => 50.00]);
});

test('webhook with invalid signature is rejected', function () {
    postWebhook([], 'invalid_signature')->assertStatus(400);
});

test('payment_intent.payment_failed webhook updates status and notifies client', function () {
    $this->paymentIntent->update(['stripe_payment_intent_id' => 'pi_failed_123']);

    postWebhook([
        'type' => 'payment_intent.payment_failed',
        'data' => ['object' => [
            'id' => 'pi_failed_123',
            'object' => 'payment_intent',
            'last_payment_error' => ['message' => 'Card declined'],
        ]],
    ])->assertOk();

    $this->paymentIntent->refresh();
    expect($this->paymentIntent->status)->toBe('failed');
    expect($this->paymentIntent->failure_reason)->toBe('Card declined');
    Notification::assertSentTo(new \Illuminate\Notifications\AnonymousNotifiable, \App\Notifications\PaymentFailedNotification::class);
});
