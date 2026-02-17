<?php

use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validSegmentPayload(string $name = 'VIP Segment'): array
{
    return [
        'name' => $name,
        'description' => 'Segment for high intent contacts',
        'filters' => [
            'group_boolean' => 'and',
            'criteria_boolean' => 'or',
            'groups' => [
                [
                    'criteria' => [
                        ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => 'Paris'],
                    ],
                ],
            ],
        ],
    ];
}

test('authenticated user can create list update and delete segment', function () {
    $user = User::factory()->create();

    $createResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/segments', validSegmentPayload());

    $createResponse->assertStatus(201)
        ->assertJsonPath('data.name', 'VIP Segment');

    $segmentId = (string) $createResponse->json('data.id');

    $listResponse = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/segments');

    $listResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    $updateResponse = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/segments/'.$segmentId, validSegmentPayload('Qualified Leads'));

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.name', 'Qualified Leads');

    $deleteResponse = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/segments/'.$segmentId);

    $deleteResponse->assertStatus(200);

    $this->assertDatabaseMissing('segments', ['id' => $segmentId]);
});

test('segment creation validates filters payload', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/segments', [
            'name' => 'Broken Segment',
            'filters' => [
                'groups' => [
                    ['criteria' => []],
                ],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['filters.groups.0.criteria']);
});

test('user cannot access another users segment', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $segment = Segment::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/segments/'.$segment->id);

    $response->assertStatus(403);
});
