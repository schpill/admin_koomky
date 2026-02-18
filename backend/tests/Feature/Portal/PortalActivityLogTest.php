<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PortalAccessToken;
use App\Models\PortalActivityLog;
use App\Models\PortalSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalActivityHeaders(Client $client): array
{
    $token = PortalAccessToken::factory()->create([
        'client_id' => $client->id,
        'email' => $client->email,
        'is_active' => true,
        'expires_at' => now()->addHour(),
    ]);

    $verifyResponse = test()->withHeaders([
        'User-Agent' => 'PortalActivityTest/1.0',
    ])->getJson('/api/v1/portal/auth/verify/'.$token->token);
    $verifyResponse->assertStatus(200);

    return [
        'Authorization' => 'Bearer '.$verifyResponse->json('data.portal_token'),
        'User-Agent' => 'PortalActivityTest/1.0',
    ];
}

test('portal activity is logged with request metadata and entity references', function () {
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

    $this->getJson('/api/v1/portal/invoices/'.$invoice->id, portalActivityHeaders($client))
        ->assertStatus(200);

    $log = PortalActivityLog::query()
        ->where('client_id', $client->id)
        ->where('action', 'view_invoice')
        ->where('entity_id', $invoice->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log?->entity_type)->toBe(Invoice::class);
    expect($log?->ip_address)->not->toBeNull();
    expect((string) $log?->user_agent)->toContain('PortalActivityTest/1.0');
});
