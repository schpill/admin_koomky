<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('can update lead status', function () {
    $lead = \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);

    $response = $this->patchJson("/api/v1/leads/{$lead->id}/status", [
        'status' => 'contacted',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'contacted');
});

test('lost status requires lost_reason', function () {
    $lead = \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);

    $response = $this->patchJson("/api/v1/leads/{$lead->id}/status", [
        'status' => 'lost',
    ]);

    $response->assertStatus(422);
});

test('can mark lead as lost with reason', function () {
    $lead = \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);

    $response = $this->patchJson("/api/v1/leads/{$lead->id}/status", [
        'status' => 'lost',
        'lost_reason' => 'Budget constraints',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'lost')
        ->assertJsonPath('data.lost_reason', 'Budget constraints')
        ->assertJsonPath('data.probability', 0);
});

test('won status sets probability to 100', function () {
    $lead = \App\Models\Lead::factory()->newLead()->create([
        'user_id' => $this->user->id,
        'probability' => 50,
    ]);

    $response = $this->patchJson("/api/v1/leads/{$lead->id}/status", [
        'status' => 'won',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.probability', 100);
});

test('can update lead position', function () {
    $lead = \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'pipeline_position' => 1,
    ]);

    $response = $this->patchJson("/api/v1/leads/{$lead->id}/position", [
        'position' => 5,
    ]);

    $response->assertStatus(200);
});
