<?php

use App\Models\ScoringRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create update list and delete their scoring rules', function () {
    $user = User::factory()->create();

    $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/scoring-rules', [
        'event' => 'email_opened',
        'points' => 15,
        'expiry_days' => 60,
        'is_active' => true,
    ]);

    $create->assertStatus(201)
        ->assertJsonPath('data.event', 'email_opened')
        ->assertJsonPath('data.points', 15);

    $ruleId = (string) $create->json('data.id');

    $index = $this->actingAs($user, 'sanctum')->getJson('/api/v1/scoring-rules');
    $index->assertStatus(200)
        ->assertJsonCount(1, 'data');

    $update = $this->actingAs($user, 'sanctum')->putJson('/api/v1/scoring-rules/'.$ruleId, [
        'points' => 25,
        'expiry_days' => 90,
        'is_active' => false,
    ]);

    $update->assertOk()
        ->assertJsonPath('data.points', 25)
        ->assertJsonPath('data.is_active', false);

    $delete = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/scoring-rules/'.$ruleId);
    $delete->assertOk();

    $this->assertDatabaseMissing('scoring_rules', ['id' => $ruleId]);
});

test('user cannot mutate another users scoring rule', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $rule = ScoringRule::query()->create([
        'user_id' => $owner->id,
        'event' => 'email_clicked',
        'points' => 20,
        'expiry_days' => 90,
        'is_active' => true,
    ]);

    $response = $this->actingAs($other, 'sanctum')->putJson('/api/v1/scoring-rules/'.$rule->id, [
        'points' => 999,
    ]);

    $response->assertStatus(404);
    expect($rule->fresh()?->points)->toBe(20);
});
