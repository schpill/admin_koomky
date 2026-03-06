<?php

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('campaign analytics includes ab variants metrics and winner flag', function () {
    $user = User::factory()->create();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'is_ab_test' => true,
    ]);

    $variantA = CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'A',
        'sent_count' => 10,
        'open_count' => 5,
        'click_count' => 2,
    ]);

    CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'B',
        'sent_count' => 10,
        'open_count' => 3,
        'click_count' => 1,
    ]);

    $campaign->update(['ab_winner_variant_id' => $variantA->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/campaigns/'.$campaign->id.'/analytics');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data.ab_variants')
        ->assertJsonPath('data.ab_variants.0.label', 'A')
        ->assertJsonPath('data.ab_variants.0.open_rate', 50.0)
        ->assertJsonPath('data.ab_variants.0.click_rate', 20.0)
        ->assertJsonPath('data.ab_variants.0.is_winner', true);
});
