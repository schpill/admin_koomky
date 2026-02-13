<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists tags for authenticated user', function () {
    Tag::factory()->count(3)->create(['user_id' => $this->user->id]);
    // Other user's tags should not appear
    Tag::factory()->count(2)->create();

    actingAs($this->user)
        ->getJson('/api/v1/tags')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('creates a new tag', function () {
    actingAs($this->user)
        ->postJson('/api/v1/tags', [
            'name' => 'VIP',
            'color' => 'blue',
        ])
        ->assertStatus(201);

    expect(Tag::where('user_id', $this->user->id)->where('name', 'VIP')->exists())->toBeTrue();
});

it('validates tag name is required', function () {
    actingAs($this->user)
        ->postJson('/api/v1/tags', [
            'color' => 'blue',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('validates color is in allowed list', function () {
    actingAs($this->user)
        ->postJson('/api/v1/tags', [
            'name' => 'Test',
            'color' => 'rainbow',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['color']);
});

it('reuses existing tag with same name for same user', function () {
    Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'VIP', 'color' => 'blue']);

    actingAs($this->user)
        ->postJson('/api/v1/tags', [
            'name' => 'VIP',
            'color' => 'red',
        ])
        ->assertStatus(200); // Not 201 since it already exists

    expect(Tag::where('user_id', $this->user->id)->where('name', 'VIP')->count())->toBe(1);
});

it('shows a tag with its clients', function () {
    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    actingAs($this->user)
        ->getJson("/api/v1/tags/{$tag->id}")
        ->assertStatus(200);
});

it('updates a tag', function () {
    $tag = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'Old']);

    actingAs($this->user)
        ->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Updated',
            'color' => 'green',
        ])
        ->assertStatus(200);

    expect($tag->fresh()->name)->toBe('Updated');
    expect($tag->fresh()->color)->toBe('green');
});

it('deletes a tag', function () {
    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    actingAs($this->user)
        ->deleteJson("/api/v1/tags/{$tag->id}")
        ->assertStatus(204);

    expect(Tag::find($tag->id))->toBeNull();
});

it('searches tags by name', function () {
    Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'VIP Client']);
    Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'Enterprise']);

    actingAs($this->user)
        ->getJson('/api/v1/tags?search=VIP')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('requires authentication', function () {
    $this->getJson('/api/v1/tags')
        ->assertStatus(401);
});

// TODO: TagController lacks authorization - any authenticated user can modify any tag
// These tests document current (insecure) behavior. Should return 403 after adding a TagPolicy.
it('allows updating another users tag (missing authorization)', function () {
    $otherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $otherUser->id]);

    actingAs($this->user)
        ->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Hacked',
            'color' => 'red',
        ])
        ->assertStatus(200);
});

it('allows deleting another users tag (missing authorization)', function () {
    $otherUser = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $otherUser->id]);

    actingAs($this->user)
        ->deleteJson("/api/v1/tags/{$tag->id}")
        ->assertStatus(204);
});
