<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    User::unsetEventDispatcher();
});

it('shows a client with relationships', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $client->tags()->attach($tag);
    Contact::factory()->create(['client_id' => $client->id]);

    actingAs($user)
        ->getJson("/api/v1/clients/{$client->id}")
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'reference',
                    'name',
                    'email',
                    'status',
                    'created_at',
                ],
                'relationships' => [
                    'tags',
                    'contacts',
                    'activities',
                ],
            ],
        ]);
});

it('forbids viewing another users client', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->for($otherUser)->create();

    actingAs($user)
        ->getJson("/api/v1/clients/{$client->id}")
        ->assertStatus(403);
});

it('returns 404 for non-existent client', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson('/api/v1/clients/00000000-0000-0000-0000-000000000000')
        ->assertStatus(404);
});

it('requires authentication to show client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();

    $this->getJson("/api/v1/clients/{$client->id}")
        ->assertStatus(401);
});
