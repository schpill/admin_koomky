<?php

use App\Http\Middleware\PortalAuthMiddleware;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PortalSettings;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

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
        $mock->shouldReceive('createPaymentIntent')->andReturn([
            'client_secret' => 'pi_mock_123_secret_xyz',
        ]);
    });

    // Authenticate as the user who owns the data
    $this->actingAs($this->user, 'sanctum');

    // Disable the portal-specific auth middleware and manually set the client context
    $this->withoutMiddleware([PortalAuthMiddleware::class]);
    $this->app['request']->attributes->set('portal_client', $this->client);
    $this->app['request']->attributes->set('portal_settings', $this->settings);
});

test('portal client can create payment intent for an unpaid invoice', function () {
    $this->postJson('/api/v1/portal/invoices/'.$this->invoice->id.'/pay')
        ->assertStatus(201)
        ->assertJsonPath('data.invoice_id', $this->invoice->id)
        ->assertJsonPath('data.client_secret', 'pi_mock_123_secret_xyz');
});

test('portal client can check payment status', function () {
    $this->postJson('/api/v1/portal/invoices/'.$this->invoice->id.'/pay');

    $this->getJson('/api/v1/portal/invoices/'.$this->invoice->id.'/payment-status')
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'processing');
});

test('portal client cannot pay an already paid invoice', function () {
    $this->invoice->update(['status' => 'paid']);

    $this->postJson('/api/v1/portal/invoices/'.$this->invoice->id.'/pay')
        ->assertStatus(422);
});

test('portal client cannot pay another client invoice', function () {
    $otherClientInvoice = Invoice::factory()->create(['user_id' => $this->user->id]);

    $this->postJson('/api/v1/portal/invoices/'.$otherClientInvoice->id.'/pay')
        ->assertStatus(404);
});
