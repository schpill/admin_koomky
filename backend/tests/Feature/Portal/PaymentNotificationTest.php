<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

const NOTIFICATION_WEBHOOK_SECRET = 'whsec_notifications_secret';

function postNotificationWebhook(array $data, ?string $signature = null): \Illuminate\Testing\TestResponse
{
    $timestamp = time();
    $payload = json_encode($data, JSON_UNESCAPED_SLASHES);

    if (! $signature) {
        $stringToSign = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $stringToSign, NOTIFICATION_WEBHOOK_SECRET);
    }

    $headers = ['Stripe-Signature' => "t={$timestamp},v1={$signature}"];

    return test()->postJson('/api/v1/webhooks/stripe', $data, $headers);
}

test('freelancer is notified when payment succeeds', function () {
    Notification::fake();
    Config::set('services.stripe.webhook_secret', NOTIFICATION_WEBHOOK_SECRET);

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
    PaymentIntent::factory()->create([
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'stripe_payment_intent_id' => 'pi_notify_success',
    ]);

    postNotificationWebhook([
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id' => 'pi_notify_success',
            'object' => 'payment_intent',
            'amount_received' => 18000,
        ]],
    ])->assertOk();

    Notification::assertSentTo($user, PaymentReceivedNotification::class);
});

test('client is notified by email when payment fails', function () {
    Notification::fake();
    Config::set('services.stripe.webhook_secret', NOTIFICATION_WEBHOOK_SECRET);

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'email' => 'billing@example.com']);
    $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
    PaymentIntent::factory()->create([
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'stripe_payment_intent_id' => 'pi_notify_failed',
    ]);

    postNotificationWebhook([
        'type' => 'payment_intent.payment_failed',
        'data' => ['object' => [
            'id' => 'pi_notify_failed',
            'object' => 'payment_intent',
            'last_payment_error' => ['message' => 'Insufficient funds'],
        ]],
    ])->assertOk();

    Notification::assertSentTo(
        new \Illuminate\Notifications\AnonymousNotifiable,
        PaymentFailedNotification::class
    );
});
