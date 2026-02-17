<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard includes campaign summary metrics', function () {
    $user = User::factory()->create();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'status' => 'sending',
        'type' => 'email',
        'created_at' => now()->subDays(5),
    ]);

    CampaignRecipient::factory()->count(2)->create([
        'campaign_id' => $campaign->id,
        'status' => 'opened',
        'delivered_at' => now()->subDays(4),
        'opened_at' => now()->subDays(3),
    ]);

    CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'clicked',
        'delivered_at' => now()->subDays(4),
        'clicked_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

    $response->assertStatus(200)
        ->assertJsonPath('data.active_campaigns_count', 1)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'average_campaign_open_rate',
                'average_campaign_click_rate',
            ],
        ]);
});
