<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('can list lead activities', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);
    \App\Models\LeadActivity::factory()->count(3)->create(['lead_id' => $lead->id]);

    $response = $this->getJson("/api/v1/leads/{$lead->id}/activities");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('can create lead activity', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);

    $response = $this->postJson("/api/v1/leads/{$lead->id}/activities", [
        'type' => 'call',
        'content' => 'Had a great conversation about their needs',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'call');
});

test('can create follow up activity with scheduled date', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);

    $response = $this->postJson("/api/v1/leads/{$lead->id}/activities", [
        'type' => 'follow_up',
        'content' => 'Schedule demo',
        'scheduled_at' => now()->addDays(7)->toIso8601String(),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'follow_up');
});

test('follow up requires scheduled_at', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);

    $response = $this->postJson("/api/v1/leads/{$lead->id}/activities", [
        'type' => 'follow_up',
        'content' => 'Schedule demo',
    ]);

    $response->assertStatus(422);
});

test('can delete lead activity', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);
    $activity = \App\Models\LeadActivity::factory()->create(['lead_id' => $lead->id]);

    $response = $this->deleteJson("/api/v1/leads/{$lead->id}/activities/{$activity->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('lead_activities', ['id' => $activity->id]);
});

test('cannot access other users lead activities', function () {
    $otherUser = \App\Models\User::factory()->create();
    $lead = \App\Models\Lead::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson("/api/v1/leads/{$lead->id}/activities");

    $response->assertStatus(404);
});
