<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('campaign report endpoints return json csv and pdf', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'sent',
    ]);

    CampaignRecipient::factory()->count(2)->create([
        'campaign_id' => $campaign->id,
        'status' => 'sent',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/campaigns/'.$campaign->id.'/report')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'summary',
                'links',
                'timeline',
            ],
        ]);

    $csvResponse = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/campaigns/'.$campaign->id.'/report/csv');

    $csvResponse->assertOk();
    expect((string) $csvResponse->headers->get('content-type'))->toContain('text/csv');

    $pdfResponse = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/campaigns/'.$campaign->id.'/report/pdf');

    $pdfResponse->assertOk();
    expect((string) $pdfResponse->headers->get('content-type'))->toContain('application/pdf');
});
