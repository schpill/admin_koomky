<?php

use App\Models\User;
use App\Models\Client;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create a tag', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', [
            'name' => 'VIP',
            'color' => '#FF0000',
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('tags', [
        'user_id' => $user->id,
        'name' => 'VIP',
    ]);
});

test('user can attach tag to a client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'Urgent']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/tags", [
            'tag_ids' => [$tag->id],
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('client_tag', [
        'client_id' => $client->id,
        'tag_id' => $tag->id,
    ]);
});
