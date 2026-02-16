<?php

use App\Models\Client;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can list their tags', function () {
    $user = User::factory()->create();
    Tag::factory()->count(3)->create(['user_id' => $user->id]);

    // Create tags owned by another user (should not appear)
    $other = User::factory()->create();
    Tag::factory()->count(2)->create(['user_id' => $other->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/tags');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'Success')
        ->assertJsonPath('message', 'Tags retrieved successfully');

    expect($response->json('data'))->toHaveCount(3);
});

test('user can delete their own tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/tags/{$tag->id}");

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Tag deleted successfully');

    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});

test('user cannot delete another users tag', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/tags/{$tag->id}");

    $response->assertStatus(403);
});

test('creating duplicate tag name for same user fails validation', function () {
    $user = User::factory()->create();
    Tag::factory()->create(['user_id' => $user->id, 'name' => 'VIP']);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', [
            'name' => 'VIP',
            'color' => '#00FF00',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

test('different users can have tags with the same name', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Tag::factory()->create(['user_id' => $userA->id, 'name' => 'VIP']);

    $response = $this->actingAs($userB, 'sanctum')
        ->postJson('/api/v1/tags', [
            'name' => 'VIP',
            'color' => '#FF0000',
        ]);

    $response->assertStatus(201);
});

test('user can attach tag to client by name', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/tags", [
            'name' => 'NewTag',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Tag attached to client');

    $this->assertDatabaseHas('tags', [
        'user_id' => $user->id,
        'name' => 'NewTag',
    ]);

    $this->assertDatabaseHas('client_tag', [
        'client_id' => $client->id,
    ]);
});

test('attaching tag by name with empty name fails', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/tags", [
            'name' => '   ',
        ]);

    $response->assertStatus(422);
});

test('user can detach tag from client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $client->tags()->attach($tag->id);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/clients/{$client->id}/tags/{$tag->id}");

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Tag detached from client');

    $this->assertDatabaseMissing('client_tag', [
        'client_id' => $client->id,
        'tag_id' => $tag->id,
    ]);
});

test('user cannot detach tag from another users client', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $otherUser->id]);
    $tag = Tag::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/clients/{$client->id}/tags/{$tag->id}");

    $response->assertStatus(403);
});

test('user cannot attach tag to another users client', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/clients/{$client->id}/tags", [
            'name' => 'MyTag',
        ]);

    $response->assertStatus(403);
});

test('tag name is required when creating', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', [
            'color' => '#FF0000',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

test('unauthenticated user cannot list tags', function () {
    $response = $this->getJson('/api/v1/tags');

    $response->assertStatus(401);
});
