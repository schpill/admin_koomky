<?php

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('gdpr export includes campaign variants only for current user campaigns', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'is_ab_test' => true,
        'type' => 'email',
    ]);

    CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'A',
        'sent_count' => 10,
    ]);

    $otherCampaign = Campaign::factory()->create([
        'user_id' => $other->id,
        'is_ab_test' => true,
        'type' => 'email',
    ]);

    CampaignVariant::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'label' => 'A',
        'sent_count' => 20,
    ]);

    $payload = app(DataExportService::class)->exportUserData($user);

    $variants = collect($payload['campaign_variants'] ?? []);

    expect($variants)->toHaveCount(1);
    expect($variants->first()['campaign_id'])->toBe($campaign->id);
});
