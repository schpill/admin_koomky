<?php

use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('campaign creation rejects invalid dynamic content', function () {
    $user = User::factory()->create();
    $segment = Segment::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/campaigns', [
        'name' => 'Dynamic Campaign',
        'type' => 'email',
        'segment_id' => $segment->id,
        'subject' => 'Subject',
        'content' => '{{#if hacker.value == "x"}}Broken{{/if}}',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('campaign creation accepts valid dynamic content in the body', function () {
    $user = User::factory()->create();
    $segment = Segment::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/campaigns', [
        'name' => 'Dynamic Campaign',
        'type' => 'email',
        'segment_id' => $segment->id,
        'subject' => 'Subject',
        'content' => '{{#if contact.first_name == "Jane"}}Hello{{else}}Hi{{/if}}',
    ]);

    $response->assertCreated();
});
