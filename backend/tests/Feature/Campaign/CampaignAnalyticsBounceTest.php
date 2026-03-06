<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('campaign analytics includes hard bounce soft bounce and suppressed counts', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
    ]);

    CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'bounced',
        'bounce_type' => 'hard',
        'bounce_count' => 1,
    ]);

    CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'bounced',
        'bounce_type' => 'soft',
        'bounce_count' => 2,
    ]);

    \App\Models\SuppressedEmail::query()->create([
        'user_id' => $user->id,
        'email' => 'suppressed@example.test',
        'reason' => 'manual',
        'suppressed_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/campaigns/'.$campaign->id.'/analytics');

    $response->assertOk()
        ->assertJsonPath('data.hard_bounce_count', 1)
        ->assertJsonPath('data.soft_bounce_count', 1)
        ->assertJsonPath('data.suppressed_count', 1);
});
