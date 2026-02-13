<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Client;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns dashboard stats for authenticated user', function () {
    actingAs($this->user)
        ->getJson('/api/v1/dashboard')
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'attributes' => [
                    'stats' => [
                        'total_clients',
                        'active_projects',
                        'pending_tasks',
                        'monthly_revenue',
                    ],
                    'recent_activity',
                ],
            ],
        ]);
});

it('returns correct client count', function () {
    Client::factory()->count(5)->create(['user_id' => $this->user->id]);
    // Other user's clients should not be counted
    Client::factory()->count(3)->create();

    $response = actingAs($this->user)
        ->getJson('/api/v1/dashboard')
        ->assertStatus(200);

    expect($response->json('data.attributes.stats.total_clients'))->toBe(5);
});

it('returns recent activities', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id]);
    Activity::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
    ]);

    $response = actingAs($this->user)
        ->getJson('/api/v1/dashboard')
        ->assertStatus(200);

    expect(count($response->json('data.attributes.recent_activity')))->toBe(3);
});

it('limits recent activities to 10', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id]);
    Activity::factory()->count(15)->create([
        'user_id' => $this->user->id,
        'client_id' => $client->id,
    ]);

    $response = actingAs($this->user)
        ->getJson('/api/v1/dashboard')
        ->assertStatus(200);

    expect(count($response->json('data.attributes.recent_activity')))->toBeLessThanOrEqual(10);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/dashboard')
        ->assertStatus(401);
});

it('does not include other users activities', function () {
    $otherUser = User::factory()->create();
    $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);
    Activity::factory()->count(5)->create([
        'user_id' => $otherUser->id,
        'client_id' => $otherClient->id,
    ]);

    // Current user has no activities
    $response = actingAs($this->user)
        ->getJson('/api/v1/dashboard')
        ->assertStatus(200);

    expect(count($response->json('data.attributes.recent_activity')))->toBe(0);
});
