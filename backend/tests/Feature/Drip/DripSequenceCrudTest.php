<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can crud drip sequences and enroll contacts or segments', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);
    $segment = Segment::factory()->create([
        'user_id' => $user->id,
        'filters' => [
            'groups' => [
                [
                    'criteria' => [
                        ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => 'Nice'],
                    ],
                ],
            ],
        ],
    ]);
    $segmentContact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id, 'city' => 'Nice'])->id,
    ]);

    $createResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/drip-sequences', [
            'name' => 'Welcome flow',
            'trigger_event' => 'manual',
            'status' => 'active',
            'steps' => [
                [
                    'position' => 1,
                    'delay_hours' => 0,
                    'condition' => 'none',
                    'subject' => 'Hello',
                    'content' => '<p>Welcome</p>',
                ],
            ],
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Welcome flow');

    $sequenceId = $createResponse->json('data.id');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/drip-sequences/'.$sequenceId.'/enroll', [
            'contact_id' => $contact->id,
        ])
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/drip-sequences/'.$sequenceId.'/enroll-segment', [
            'segment_id' => $segment->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.enrolled', 1);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/drip-enrollments/'.DripEnrollment::query()->where('contact_id', $contact->id)->value('id').'/pause')
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/drip-enrollments/'.DripEnrollment::query()->where('contact_id', $contact->id)->value('id').'/resume')
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/drip-enrollments/'.DripEnrollment::query()->where('contact_id', $segmentContact->id)->value('id').'/cancel')
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/drip-sequences/'.$sequenceId)
        ->assertOk()
        ->assertJsonCount(1, 'data.steps');

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/drip-sequences/'.$sequenceId, [
            'name' => 'Welcome flow v2',
            'trigger_event' => 'manual',
            'status' => 'paused',
            'steps' => [
                [
                    'position' => 1,
                    'delay_hours' => 0,
                    'condition' => 'none',
                    'subject' => 'Hello again',
                    'content' => '<p>Updated</p>',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'paused');

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/drip-sequences/'.$sequenceId)
        ->assertNoContent();
});

test('user cannot access another users drip sequence', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $sequence = DripSequence::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($intruder, 'sanctum')
        ->getJson('/api/v1/drip-sequences/'.$sequence->id)
        ->assertForbidden();
});
