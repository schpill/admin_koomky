<?php

use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalSettingsSessionHeaders(Client $client): array
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

test('admin can update portal settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/portal', [
            'portal_enabled' => true,
            'custom_color' => '#123ABC',
            'welcome_message' => 'Hello from portal settings',
            'payment_enabled' => true,
            'quote_acceptance_enabled' => true,
        ]);

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.portal_enabled', true)
        ->assertJsonPath('data.custom_color', '#123ABC');

    $this->assertDatabaseHas('portal_settings', [
        'user_id' => $user->id,
        'portal_enabled' => true,
        'custom_color' => '#123ABC',
        'payment_enabled' => true,
    ]);
});

test('portal routes return 403 when portal is disabled', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => false,
    ]);

    $this->getJson('/api/v1/portal/dashboard', portalSettingsSessionHeaders($client))
        ->assertStatus(403);
});
