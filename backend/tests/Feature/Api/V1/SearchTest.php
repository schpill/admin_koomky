<?php

use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search returns empty results when no query provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/search');

    $response->assertStatus(200)
        ->assertJsonPath('data', []);
});

test('search returns success with query', function () {
    $user = User::factory()->create();
    
    // We can't easily test real Meilisearch results here as Scout is disabled in phpunit.xml
    // but we can check if the controller responds correctly.
    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/search?q=test');

    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'data' => ['clients']]);
});
