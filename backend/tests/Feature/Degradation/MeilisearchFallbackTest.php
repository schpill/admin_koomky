<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search falls back to database when meilisearch is unavailable', function () {
    config()->set('scout.driver', 'meilisearch');
    config()->set('scout.force_failure', true);

    $user = User::factory()->create();
    Client::factory()->for($user)->create([
        'name' => 'Acme Fallback Co',
        'email' => 'acme@example.test',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/search?q=Acme');

    $response->assertOk()
        ->assertJsonPath('data.clients.0.name', 'Acme Fallback Co');
});
