<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('can get pipeline grouped by status', function () {
    \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);
    \App\Models\Lead::factory()->won()->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/v1/leads/pipeline');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'columns',
                'column_stats',
                'total_pipeline_value',
            ],
        ]);
});

test('pipeline includes correct counts and totals', function () {
    \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'new',
        'estimated_value' => 10000,
    ]);
    \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'new',
        'estimated_value' => 20000,
    ]);

    $response = $this->getJson('/api/v1/leads/pipeline');

    $response->assertStatus(200)
        ->assertJsonPath('data.column_stats.new.count', 2)
        ->assertJsonPath('data.column_stats.new.total_value', 30000.00);
});

test('pipeline excludes won and lost from total pipeline value', function () {
    \App\Models\Lead::factory()->newLead()->create([
        'user_id' => $this->user->id,
        'estimated_value' => 10000,
    ]);
    \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'estimated_value' => 50000,
    ]);

    $response = $this->getJson('/api/v1/leads/pipeline');

    $response->assertStatus(200)
        ->assertJsonPath('data.total_pipeline_value', 10000.00);
});
