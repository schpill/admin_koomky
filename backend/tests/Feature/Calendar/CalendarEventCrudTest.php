<?php

use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validCalendarEventPayload(string $connectionId): array
{
    return [
        'calendar_connection_id' => $connectionId,
        'title' => 'Kickoff call',
        'description' => 'Initial kickoff with client',
        'start_at' => '2026-03-10 09:00:00',
        'end_at' => '2026-03-10 10:00:00',
        'all_day' => false,
        'location' => 'Remote',
        'type' => 'meeting',
        'sync_status' => 'local',
    ];
}

test('user can create read update list and delete calendar events', function () {
    $user = User::factory()->create();
    $connection = CalendarConnection::factory()->create([
        'user_id' => $user->id,
    ]);

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/calendar-events', validCalendarEventPayload($connection->id));

    $create->assertStatus(201)
        ->assertJsonPath('data.title', 'Kickoff call')
        ->assertJsonPath('data.type', 'meeting');

    $eventId = (string) $create->json('data.id');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/calendar-events/'.$eventId)
        ->assertStatus(200)
        ->assertJsonPath('data.id', $eventId);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/calendar-events/'.$eventId, [
            'title' => 'Kickoff call updated',
            'type' => 'meeting',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.title', 'Kickoff call updated');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/calendar-events?date_from=2026-03-01&date_to=2026-03-31&type=meeting')
        ->assertStatus(200)
        ->assertJsonPath('data.0.id', $eventId);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/calendar-events/'.$eventId)
        ->assertStatus(200);

    $this->assertDatabaseMissing('calendar_events', ['id' => $eventId]);
});

test('user cannot access another user event', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $event = CalendarEvent::factory()->create([
        'user_id' => $owner->id,
    ]);

    $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/calendar-events/'.$event->id)
        ->assertStatus(403);
});
