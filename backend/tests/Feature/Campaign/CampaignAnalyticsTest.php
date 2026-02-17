<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('analytics endpoint returns metrics and time series', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'sent',
    ]);

    CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'opened',
        'opened_at' => now()->subHour(),
        'delivered_at' => now()->subHours(2),
    ]);

    CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'clicked',
        'clicked_at' => now()->subMinutes(30),
        'delivered_at' => now()->subHours(2),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/campaigns/'.$campaign->id.'/analytics');

    $response->assertStatus(200)
        ->assertJsonPath('data.total_recipients', 2)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'open_rate',
                'click_rate',
                'time_series',
            ],
        ]);
});

test('campaign comparison endpoint returns side by side metrics', function () {
    $user = User::factory()->create();
    $campaignA = Campaign::factory()->create(['user_id' => $user->id]);
    $campaignB = Campaign::factory()->create(['user_id' => $user->id]);

    CampaignRecipient::factory()->count(2)->create(['campaign_id' => $campaignA->id, 'status' => 'sent']);
    CampaignRecipient::factory()->count(3)->create(['campaign_id' => $campaignB->id, 'status' => 'sent']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/campaigns/compare?ids='.$campaignA->id.','.$campaignB->id);

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});
