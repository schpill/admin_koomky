<?php

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validAbCampaignPayload(): array
{
    return [
        'name' => 'AB Campaign',
        'type' => 'email',
        'is_ab_test' => true,
        'variants' => [
            [
                'label' => 'A',
                'subject' => 'Subject A',
                'content' => '<p>A</p>',
                'send_percent' => 50,
            ],
            [
                'label' => 'B',
                'subject' => 'Subject B',
                'content' => '<p>B</p>',
                'send_percent' => 50,
            ],
        ],
        'ab_winner_criteria' => 'open_rate',
        'ab_auto_select_after_hours' => 24,
    ];
}

test('campaign ab test crud and winner selection works', function () {
    $user = User::factory()->create();

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns', validAbCampaignPayload());

    $create->assertStatus(201)
        ->assertJsonPath('data.is_ab_test', true)
        ->assertJsonCount(2, 'data.variants');

    $campaignId = (string) $create->json('data.id');

    $show = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/campaigns/'.$campaignId);

    $show->assertStatus(200)
        ->assertJsonCount(2, 'data.variants');

    $campaign = Campaign::query()->findOrFail($campaignId);
    $winner = $campaign->variants()->where('label', 'A')->firstOrFail();

    $select = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaignId.'/ab/select-winner', [
            'variant_id' => $winner->id,
        ]);

    $select->assertStatus(200)
        ->assertJsonPath('data.ab_winner_variant_id', $winner->id);
});

test('campaign ab test validation rejects invalid split and invalid variant set', function () {
    $user = User::factory()->create();

    $badSplit = validAbCampaignPayload();
    $badSplit['variants'][0]['send_percent'] = 40;
    $badSplit['variants'][1]['send_percent'] = 40;

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns', $badSplit)
        ->assertStatus(422);

    $tooMany = validAbCampaignPayload();
    $tooMany['variants'][] = [
        'label' => 'B',
        'subject' => 'extra',
        'content' => 'extra',
        'send_percent' => 10,
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns', $tooMany)
        ->assertStatus(422);
});

test('campaign ab winner selection validates variant ownership and policy', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $campaign = Campaign::factory()->create([
        'user_id' => $owner->id,
        'type' => 'email',
        'is_ab_test' => true,
    ]);

    CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'A',
    ]);

    $foreignVariant = CampaignVariant::factory()->create([
        'campaign_id' => Campaign::factory()->create(['user_id' => $owner->id])->id,
        'label' => 'A',
    ]);

    $this->actingAs($owner, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/ab/select-winner', [
            'variant_id' => $foreignVariant->id,
        ])
        ->assertStatus(422);

    $this->actingAs($other, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/ab/select-winner', [
            'variant_id' => $foreignVariant->id,
        ])
        ->assertStatus(403);
});
