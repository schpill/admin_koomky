<?php

use App\Jobs\SelectAbWinnerJob;
use App\Models\Campaign;
use App\Models\CampaignVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('select ab winner job chooses variant with highest open rate', function () {
    $campaign = Campaign::factory()->create([
        'is_ab_test' => true,
        'ab_winner_criteria' => 'open_rate',
    ]);

    $variantA = CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'A',
        'sent_count' => 100,
        'open_count' => 50,
        'click_count' => 10,
    ]);

    CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'B',
        'sent_count' => 100,
        'open_count' => 40,
        'click_count' => 20,
    ]);

    (new SelectAbWinnerJob($campaign->id))->handle();

    expect($campaign->fresh()->ab_winner_variant_id)->toBe($variantA->id);
    expect($campaign->fresh()->ab_winner_selected_at)->not->toBeNull();
});

test('select ab winner job chooses variant with highest click rate when configured', function () {
    $campaign = Campaign::factory()->create([
        'is_ab_test' => true,
        'ab_winner_criteria' => 'click_rate',
    ]);

    CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'A',
        'sent_count' => 100,
        'open_count' => 70,
        'click_count' => 10,
    ]);

    $variantB = CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'B',
        'sent_count' => 100,
        'open_count' => 40,
        'click_count' => 30,
    ]);

    (new SelectAbWinnerJob($campaign->id))->handle();

    expect($campaign->fresh()->ab_winner_variant_id)->toBe($variantB->id);
});

test('select ab winner job does nothing if winner already selected or campaign not ab', function () {
    $campaign = Campaign::factory()->create([
        'is_ab_test' => true,
        'ab_winner_variant_id' => null,
    ]);
    $existingWinner = CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'A',
    ]);
    $campaign->update(['ab_winner_variant_id' => $existingWinner->id]);

    (new SelectAbWinnerJob($campaign->id))->handle();
    expect($campaign->fresh()->ab_winner_variant_id)->toBe($existingWinner->id);

    $nonAb = Campaign::factory()->create(['is_ab_test' => false]);
    (new SelectAbWinnerJob($nonAb->id))->handle();
    expect($nonAb->fresh()->ab_winner_variant_id)->toBeNull();
});
