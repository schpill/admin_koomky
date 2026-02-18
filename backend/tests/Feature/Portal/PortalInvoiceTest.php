<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalInvoiceHeaders(Client $client): array
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

test('portal invoice list only returns invoices for the authenticated client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);
    $otherClient = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $visibleSent = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);
    $visiblePaid = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
    ]);
    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);
    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $otherClient->id,
        'status' => 'sent',
    ]);

    $response = $this->getJson('/api/v1/portal/invoices', portalInvoiceHeaders($client));
    $response->assertStatus(200);

    $invoiceIds = collect($response->json('data.data', []))
        ->pluck('id')
        ->values()
        ->all();

    expect($invoiceIds)->toContain($visibleSent->id);
    expect($invoiceIds)->toContain($visiblePaid->id);
    expect($invoiceIds)->toHaveCount(2);
});

test('portal client can view invoice detail', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    $this->getJson('/api/v1/portal/invoices/'.$invoice->id, portalInvoiceHeaders($client))
        ->assertStatus(200)
        ->assertJsonPath('data.id', $invoice->id);
});

test('portal client can download invoice pdf', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    $response = $this->get('/api/v1/portal/invoices/'.$invoice->id.'/pdf', portalInvoiceHeaders($client));
    $response->assertStatus(200);

    expect((string) $response->headers->get('content-type'))->toContain('application/pdf');
});

test('portal client cannot view draft invoices', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $this->getJson('/api/v1/portal/invoices/'.$invoice->id, portalInvoiceHeaders($client))
        ->assertStatus(404);
});

test('portal client cannot access invoices from another client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);
    $otherClient = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $otherClient->id,
        'status' => 'sent',
    ]);

    $this->getJson('/api/v1/portal/invoices/'.$invoice->id, portalInvoiceHeaders($client))
        ->assertStatus(404);
});
