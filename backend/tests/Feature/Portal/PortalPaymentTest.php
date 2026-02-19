<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

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

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);

    $this->settings = PortalSettings::factory()->create([
        'user_id' => $this->user->id,
        'portal_enabled' => true,
        'payment_enabled' => true,
        'stripe_publishable_key' => 'pk_test',
        'stripe_secret_key' => 'sk_test',
    ]);

    $this->invoice = Invoice::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'status' => 'sent',
        'total' => 140,
    ]);

    $this->partialMock(StripePaymentService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createPaymentIntent')
            ->andReturnUsing(function (PaymentIntent $paymentIntent): array {
                $paymentIntent->forceFill([
                    'status' => 'processing',
                    'stripe_payment_intent_id' => 'pi_mock_123',
                ])->save();

                return [
                    'client_secret' => 'pi_mock_123_secret_xyz',
                ];
            });
    });

    $this->headers = portalPaymentHeaders($this->client);
});

test('portal client can create payment intent for an unpaid invoice', function () {
    $this->postJson('/api/v1/portal/invoices/'.$this->invoice->id.'/pay', [], $this->headers)
        ->assertStatus(201)
        ->assertJsonPath('data.invoice_id', $this->invoice->id)
        ->assertJsonPath('data.client_secret', 'pi_mock_123_secret_xyz');
});

test('portal client can check payment status', function () {
    $this->postJson('/api/v1/portal/invoices/'.$this->invoice->id.'/pay', [], $this->headers);

    $this->getJson('/api/v1/portal/invoices/'.$this->invoice->id.'/payment-status', $this->headers)
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'processing');
});

test('portal client cannot pay an already paid invoice', function () {
    $this->invoice->update(['status' => 'paid']);

    $this->postJson('/api/v1/portal/invoices/'.$this->invoice->id.'/pay', [], $this->headers)
        ->assertStatus(422);
});

test('portal client cannot pay another client invoice', function () {
    $otherClientInvoice = Invoice::factory()->create(['user_id' => $this->user->id]);

    $this->postJson('/api/v1/portal/invoices/'.$otherClientInvoice->id.'/pay', [], $this->headers)
        ->assertStatus(404);
});
