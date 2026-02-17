<?php

use App\Models\Campaign;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validCampaignPayload(string $segmentId): array
{
    return [
        'name' => 'Welcome Campaign',
        'type' => 'email',
        'segment_id' => $segmentId,
        'subject' => 'Welcome {{first_name}}',
        'content' => 'Hello {{first_name}}, thanks for joining us.',
        'status' => 'draft',
        'settings' => ['throttle_rate_per_minute' => 100],
    ];
}

test('user can create read update and delete campaign', function () {
    $user = User::factory()->create();
    $segment = Segment::factory()->create(['user_id' => $user->id]);

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns', validCampaignPayload($segment->id));

    $create->assertStatus(201)
        ->assertJsonPath('data.name', 'Welcome Campaign');

    $campaignId = (string) $create->json('data.id');

    $index = $this->actingAs($user, 'sanctum')->getJson('/api/v1/campaigns?type=email&status=draft');
    $index->assertStatus(200)->assertJsonCount(1, 'data.data');

    $update = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/campaigns/'.$campaignId, [
            'name' => 'Updated Campaign',
            'content' => 'Updated body',
        ]);

    $update->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Campaign');

    $delete = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/campaigns/'.$campaignId);

    $delete->assertStatus(200);
    $this->assertDatabaseMissing('campaigns', ['id' => $campaignId]);
});

test('campaign validation enforces required email fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns', [
            'name' => 'Broken Campaign',
            'type' => 'email',
            'content' => '',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['subject', 'content']);
});

test('user cannot access another users campaign', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $campaign = Campaign::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/campaigns/'.$campaign->id);

    $response->assertStatus(403);
});
