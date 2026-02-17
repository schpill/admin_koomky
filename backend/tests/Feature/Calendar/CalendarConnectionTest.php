<?php

use App\Models\CalendarConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function validCalendarConnectionPayload(): array
{
    return [
        'provider' => 'google',
        'name' => 'Google Work',
        'calendar_id' => 'primary',
        'credentials' => [
            'access_token' => 'plain-access-token',
            'refresh_token' => 'plain-refresh-token',
        ],
        'sync_enabled' => true,
    ];
}

test('user can create update list test and delete calendar connection', function () {
    $user = User::factory()->create();

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/calendar-connections', validCalendarConnectionPayload());

    $create->assertStatus(201)
        ->assertJsonPath('data.provider', 'google')
        ->assertJsonPath('data.name', 'Google Work')
        ->assertJsonPath('data.sync_enabled', true);

    $connectionId = (string) $create->json('data.id');

    $rawCredentials = (string) DB::table('calendar_connections')
        ->where('id', $connectionId)
        ->value('credentials');
    expect($rawCredentials)->not->toContain('plain-access-token');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/calendar-connections')
        ->assertStatus(200)
        ->assertJsonPath('data.0.id', $connectionId);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/calendar-connections/'.$connectionId, [
            'name' => 'Google Personal',
            'sync_enabled' => false,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.name', 'Google Personal')
        ->assertJsonPath('data.sync_enabled', false);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/calendar-connections/'.$connectionId.'/test')
        ->assertStatus(200)
        ->assertJsonPath('data.ok', true);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/calendar-connections/google/callback?code=test-code&state='.$connectionId)
        ->assertStatus(200)
        ->assertJsonPath('data.connection_id', $connectionId);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/calendar-connections/'.$connectionId)
        ->assertStatus(200);

    $this->assertDatabaseMissing('calendar_connections', ['id' => $connectionId]);
});

test('user cannot access another user calendar connection', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $connection = CalendarConnection::factory()->create([
        'user_id' => $owner->id,
    ]);

    $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/calendar-connections/'.$connection->id)
        ->assertStatus(403);
});
