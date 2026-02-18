<?php

use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteAcceptedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function portalQuoteHeaders(Client $client): array
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

test('portal quote list only returns quotes for the authenticated client', function () {
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

    $visible = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);
    Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);
    Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $otherClient->id,
        'status' => 'sent',
    ]);

    $response = $this->getJson('/api/v1/portal/quotes', portalQuoteHeaders($client));
    $response->assertStatus(200);

    $quoteIds = collect($response->json('data.data', []))
        ->pluck('id')
        ->values()
        ->all();

    expect($quoteIds)->toContain($visible->id);
    expect($quoteIds)->toHaveCount(1);
});

test('portal client can view quote detail', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    $this->getJson('/api/v1/portal/quotes/'.$quote->id, portalQuoteHeaders($client))
        ->assertStatus(200)
        ->assertJsonPath('data.id', $quote->id);
});

test('portal client can accept quote', function () {
    Notification::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    $this->postJson('/api/v1/portal/quotes/'.$quote->id.'/accept', [], portalQuoteHeaders($client))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'accepted');

    $quote->refresh();

    expect($quote->status)->toBe('accepted');
    expect($quote->accepted_at)->not->toBeNull();

    Notification::assertSentTo($user, QuoteAcceptedNotification::class);
});

test('portal client can reject quote with reason', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    $this->postJson('/api/v1/portal/quotes/'.$quote->id.'/reject', [
        'reason' => 'Budget constraint',
    ], portalQuoteHeaders($client))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'rejected');

    $quote->refresh();
    expect($quote->status)->toBe('rejected');
});

test('accepted quote cannot be accepted again', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);

    PortalSettings::query()->create([
        'user_id' => $user->id,
        'portal_enabled' => true,
    ]);

    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $this->postJson('/api/v1/portal/quotes/'.$quote->id.'/accept', [], portalQuoteHeaders($client))
        ->assertStatus(422);
});
