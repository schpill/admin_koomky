<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalDashboardHeaders(Client $client): array
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

test('portal dashboard returns outstanding totals and recent items', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
        'welcome_message' => 'Welcome to your workspace',
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'total' => 100,
    ]);
    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'overdue',
        'total' => 50,
    ]);
    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'total' => 90,
    ]);

    Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);
    Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'accepted',
    ]);

    $response = $this->getJson('/api/v1/portal/dashboard', portalDashboardHeaders($client));

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.outstanding_invoices.count', 2)
        ->assertJsonPath('data.outstanding_invoices.total', 150.0)
        ->assertJsonPath('data.welcome_message', 'Welcome to your workspace');

    expect($response->json('data.recent_invoices'))->toBeArray();
    expect($response->json('data.recent_quotes'))->toBeArray();
});
