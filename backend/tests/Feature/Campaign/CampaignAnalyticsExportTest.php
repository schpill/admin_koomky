<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('analytics export returns csv with expected headers', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    CampaignRecipient::factory()->count(2)->create([
        'campaign_id' => $campaign->id,
        'status' => 'sent',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/campaigns/'.$campaign->id.'/analytics/export');

    $response->assertStatus(200);
    expect((string) $response->headers->get('content-type'))->toContain('text/csv');
});
