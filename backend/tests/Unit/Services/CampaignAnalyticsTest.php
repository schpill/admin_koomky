<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use App\Services\CampaignAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('campaign analytics computes rates and handles zero division', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'email']);

    CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => 'sent']);
    CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => 'delivered']);
    CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => 'opened']);
    CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => 'clicked']);
    CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => 'bounced']);
    CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => 'failed']);

    $service = app(CampaignAnalyticsService::class);
    $metrics = $service->forCampaign($campaign);

    expect($metrics['total_recipients'])->toBe(6);
    expect($metrics['open_rate'])->toBeGreaterThanOrEqual(0.0);
    expect($metrics['click_rate'])->toBeGreaterThanOrEqual(0.0);
    expect($metrics['bounce_rate'])->toBeGreaterThanOrEqual(0.0);

    $emptyCampaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'email']);
    $emptyMetrics = $service->forCampaign($emptyCampaign);

    expect($emptyMetrics['open_rate'])->toBe(0.0);
    expect($emptyMetrics['click_rate'])->toBe(0.0);
    expect($emptyMetrics['bounce_rate'])->toBe(0.0);
});
