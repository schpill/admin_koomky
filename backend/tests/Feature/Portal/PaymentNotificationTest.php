<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\PortalSettings;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function paymentWebhookHeaders(array $payload, string $secret): array
{
    $json = json_encode($payload, JSON_THROW_ON_ERROR);

    return [
        'Stripe-Signature' => hash_hmac('sha256', $json, $secret),
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];
}

test('freelancer is notified when payment succeeds', function () {
    Notification::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'stripe_webhook_secret' => 'whsec_notifications',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 180,
    ]);

    PaymentIntent::factory()->create([
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'stripe_payment_intent_id' => 'pi_notify_success',
        'amount' => 180,
        'status' => 'processing',
    ]);

    $payload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_notify_success',
                'amount_received' => 18000,
            ],
        ],
    ];

    $this->postJson('/api/v1/webhooks/stripe', $payload, paymentWebhookHeaders($payload, 'whsec_notifications'))
        ->assertStatus(200);

    Notification::assertSentTo($user, PaymentReceivedNotification::class);
});

test('client is notified by email when payment fails', function () {
    Notification::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'billing-client@example.test',
    ]);

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'stripe_webhook_secret' => 'whsec_notifications',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 95,
    ]);

    PaymentIntent::factory()->create([
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'stripe_payment_intent_id' => 'pi_notify_failed',
        'amount' => 95,
        'status' => 'processing',
    ]);

    $payload = [
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_notify_failed',
                'last_payment_error' => [
                    'message' => 'Insufficient funds',
                ],
            ],
        ],
    ];

    $this->postJson('/api/v1/webhooks/stripe', $payload, paymentWebhookHeaders($payload, 'whsec_notifications'))
        ->assertStatus(200);

    Notification::assertSentOnDemand(PaymentFailedNotification::class);
});
