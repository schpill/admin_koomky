<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
});

test('it can search by date range', function () {
    Document::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(10),
    ]);
    Document::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/documents?date_from='.now()->subDays(5)->toDateString());

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('it filters by multiple tags', function () {
    Document::factory()->create(['user_id' => $this->user->id, 'tags' => ['urgent', 'billing']]);
    Document::factory()->create(['user_id' => $this->user->id, 'tags' => ['personal']]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/documents?tag=urgent');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.tags.0', 'urgent');
});

test('it handles search query parameter without error', function () {
    Document::factory()->create(['user_id' => $this->user->id, 'title' => 'Invoice January']);
    Document::factory()->create(['user_id' => $this->user->id, 'title' => 'Contract February']);

    // Scout uses null driver in tests — search returns empty IDs, resulting in 0 hits.
    // We assert the endpoint responds correctly (200, no 500), not the search engine results.
    $response = $this->actingAs($this->user)->getJson('/api/v1/documents?q=Invoice');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'current_page', 'total']);
});

test('it combines date range and tag filters', function () {
    Document::factory()->create([
        'user_id' => $this->user->id,
        'tags' => ['billing'],
        'created_at' => now(),
    ]);
    Document::factory()->create([
        'user_id' => $this->user->id,
        'tags' => ['billing'],
        'created_at' => now()->subDays(30),
    ]);

    $response = $this->actingAs($this->user)->getJson(
        '/api/v1/documents?tag=billing&date_from='.now()->subDays(5)->toDateString()
    );

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});
