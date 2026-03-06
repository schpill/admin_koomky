<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('campaign links endpoint returns per-url stats sorted by clicks and csv export streams rows', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'sent',
    ]);

    $recipientA = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'clicked',
        'delivered_at' => now()->subHour(),
        'clicked_at' => now()->subMinutes(30),
    ]);
    $recipientB = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'clicked',
        'delivered_at' => now()->subHour(),
        'clicked_at' => now()->subMinutes(20),
    ]);

    DB::table('campaign_link_clicks')->insert([
        [
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'recipient_id' => $recipientA->id,
            'contact_id' => $recipientA->contact_id,
            'url' => 'https://example.com/a',
            'clicked_at' => now()->subMinutes(30),
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'recipient_id' => $recipientB->id,
            'contact_id' => $recipientB->contact_id,
            'url' => 'https://example.com/a',
            'clicked_at' => now()->subMinutes(20),
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'recipient_id' => $recipientA->id,
            'contact_id' => $recipientA->contact_id,
            'url' => 'https://example.com/b',
            'clicked_at' => now()->subMinutes(10),
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/campaigns/'.$campaign->id.'/links');

    $response->assertOk()
        ->assertJsonPath('data.0.url', 'https://example.com/a')
        ->assertJsonPath('data.0.total_clicks', 2)
        ->assertJsonPath('data.0.unique_clicks', 2)
        ->assertJsonPath('data.0.click_rate', 100.0)
        ->assertJsonPath('data.1.url', 'https://example.com/b')
        ->assertJsonPath('data.1.total_clicks', 1)
        ->assertJsonPath('data.1.unique_clicks', 1)
        ->assertJsonPath('data.1.click_rate', 50.0);

    $csvResponse = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/campaigns/'.$campaign->id.'/links/export');

    $csvResponse->assertOk();

    $content = $csvResponse->streamedContent();

    expect($content)
        ->toContain("url,total_clicks,unique_clicks,click_rate\n")
        ->toContain('https://example.com/a,2,2,100')
        ->toContain('https://example.com/b,1,1,50');
});
