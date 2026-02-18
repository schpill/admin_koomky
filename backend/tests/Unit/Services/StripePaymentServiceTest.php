<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\PortalSettings;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('stripe payment service creates confirms and refunds payment intents', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 120,
    ]);

    $settings = PortalSettings::factory()->create([
        'user_id' => $user->id,
        'stripe_publishable_key' => 'pk_test_123',
        'stripe_secret_key' => 'sk_test_123',
        'payment_enabled' => true,
    ]);

    $paymentIntent = PaymentIntent::factory()->create([
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'amount' => 120,
        'currency' => 'EUR',
        'status' => 'pending',
        'stripe_payment_intent_id' => null,
    ]);

    $service = app(StripePaymentService::class);

    $created = $service->createPaymentIntent($paymentIntent, $settings);
    expect($created['stripe_payment_intent_id'])->toBeString();
    expect($created['client_secret'])->toBeString();
    expect($created['status'])->toBe('processing');

    $paymentIntent->refresh();
    expect($paymentIntent->stripe_payment_intent_id)->toBe($created['stripe_payment_intent_id']);

    $confirmed = $service->confirmPayment($paymentIntent, $settings);
    expect($confirmed['status'])->toBe($paymentIntent->fresh()->status);

    $refunded = $service->refundPayment($paymentIntent->fresh(), 120, $settings);
    expect($refunded['status'])->toBe('refunded');
});

test('stripe payment service returns key configuration from portal settings', function () {
    $user = User::factory()->create();

    PortalSettings::factory()->create([
        'user_id' => $user->id,
        'stripe_publishable_key' => 'pk_dynamic',
        'stripe_secret_key' => 'sk_dynamic',
        'stripe_webhook_secret' => 'whsec_dynamic',
    ]);

    $service = app(StripePaymentService::class);
    $configuration = $service->configurationForUser($user);

    expect($configuration['publishable_key'])->toBe('pk_dynamic');
    expect($configuration['secret_key'])->toBe('sk_dynamic');
    expect($configuration['webhook_secret'])->toBe('whsec_dynamic');
});
