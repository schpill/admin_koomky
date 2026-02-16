<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list their activities', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    // Client creation via observer already logs one activity
    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/activities');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'Success')
        ->assertJsonPath('message', 'Activities retrieved successfully');
});

test('activities are paginated', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    // Client observer already created 1 activity, add 19 more for 20 total
    for ($i = 0; $i < 19; $i++) {
        Activity::create([
            'user_id' => $user->id,
            'subject_id' => $client->id,
            'subject_type' => Client::class,
            'description' => "Activity $i",
        ]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/activities?per_page=5');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data['data'])->toHaveCount(5);
    expect($data['meta']['per_page'])->toBe(5);
    expect($data['meta']['total'])->toBe(20);
});

test('activities can be filtered by subject_id', function () {
    $user = User::factory()->create();
    $clientA = Client::factory()->create(['user_id' => $user->id]);
    $clientB = Client::factory()->create(['user_id' => $user->id]);

    // Observer created one activity per client. Filter by clientA should show
    // only the observer-created activity for clientA plus any manual ones.
    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/activities?subject_id={$clientA->id}");

    $response->assertStatus(200);
    $data = $response->json('data.data');
    // Only the observer-created activity for clientA
    expect($data)->toHaveCount(1);
    expect($data[0]['subject_id'])->toBe($clientA->id);
});

test('activities can be filtered by subject_type', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    // Observer already created a Client-type activity
    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/activities?subject_type=Client');

    $response->assertStatus(200);
    $data = $response->json('data.data');
    // At least the observer-created activity
    expect(count($data))->toBeGreaterThanOrEqual(1);
    expect($data[0]['subject_type'])->toBe('Client');
});

test('user cannot see other users activities', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/activities');

    $response->assertStatus(200);
    $data = $response->json('data.data');
    expect($data)->toHaveCount(0);
});

test('unauthenticated user cannot list activities', function () {
    $response = $this->getJson('/api/v1/activities');

    $response->assertStatus(401);
});

test('activities are returned in descending order by created_at', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    // Delete observer-created activities to have clean slate
    Activity::query()->delete();

    $older = Activity::create([
        'user_id' => $user->id,
        'subject_id' => $client->id,
        'subject_type' => Client::class,
        'description' => 'Older activity',
    ]);
    // Force a past timestamp via direct DB update
    \Illuminate\Support\Facades\DB::table('activities')
        ->where('id', $older->id)
        ->update(['created_at' => now()->subHour()]);

    $newer = Activity::create([
        'user_id' => $user->id,
        'subject_id' => $client->id,
        'subject_type' => Client::class,
        'description' => 'Newer activity',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/activities');

    $response->assertStatus(200);
    $data = $response->json('data.data');
    expect($data[0]['description'])->toBe('Newer activity');
    expect($data[1]['description'])->toBe('Older activity');
});

test('activity resource includes expected fields', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    // Delete observer-created activities to have clean slate
    Activity::query()->delete();

    Activity::create([
        'user_id' => $user->id,
        'subject_id' => $client->id,
        'subject_type' => Client::class,
        'description' => 'Test description',
        'metadata' => ['key' => 'value'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/activities');

    $response->assertStatus(200);
    $activity = $response->json('data.data.0');
    expect($activity)->toHaveKeys(['id', 'description', 'subject_id', 'subject_type', 'metadata', 'created_at']);
    expect($activity['description'])->toBe('Test description');
    expect($activity['metadata'])->toBe(['key' => 'value']);
    expect($activity['subject_type'])->toBe('Client');
});
