<?php

use App\Jobs\SyncCalendarJob;
use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('calendar sync command dispatches jobs for active connections only', function () {
    Queue::fake();

    $active = CalendarConnection::factory()->create(['sync_enabled' => true]);
    CalendarConnection::factory()->create(['sync_enabled' => false]);

    $this->artisan('calendar:sync')
        ->assertExitCode(0);

    Queue::assertPushed(SyncCalendarJob::class, function (SyncCalendarJob $job) use ($active): bool {
        return $job->connectionId === $active->id;
    });
    Queue::assertPushed(SyncCalendarJob::class, 1);
});

test('sync job imports remote events and flags conflict with local newer event', function () {
    Carbon::setTestNow('2026-03-05 09:00:00');

    $user = User::factory()->create();
    $connection = CalendarConnection::factory()->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'calendar_id' => 'primary',
        'sync_enabled' => true,
        'credentials' => [
            'access_token' => 'google-token',
            'refresh_token' => 'google-refresh',
        ],
    ]);

    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'calendar_connection_id' => $connection->id,
        'external_id' => 'remote_evt_conflict',
        'title' => 'Local updated title',
        'start_at' => '2026-03-10 10:00:00',
        'end_at' => '2026-03-10 11:00:00',
        'sync_status' => 'local',
        'updated_at' => Carbon::parse('2026-03-05 10:00:00'),
    ]);

    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*/events*' => Http::response([
            'items' => [
                [
                    'id' => 'remote_evt_new',
                    'summary' => 'Imported from remote',
                    'description' => 'Remote description',
                    'start' => ['dateTime' => '2026-03-11T09:00:00Z'],
                    'end' => ['dateTime' => '2026-03-11T10:00:00Z'],
                    'updated' => '2026-03-05T08:00:00Z',
                    'location' => 'Remote',
                ],
                [
                    'id' => 'remote_evt_conflict',
                    'summary' => 'Older remote title',
                    'description' => 'Older remote version',
                    'start' => ['dateTime' => '2026-03-10T10:00:00Z'],
                    'end' => ['dateTime' => '2026-03-10T11:00:00Z'],
                    'updated' => '2026-03-05T08:00:00Z',
                    'location' => 'Remote',
                ],
            ],
        ], 200),
    ]);

    $job = new SyncCalendarJob($connection->id);
    $job->handle();

    $this->assertDatabaseHas('calendar_events', [
        'user_id' => $user->id,
        'calendar_connection_id' => $connection->id,
        'external_id' => 'remote_evt_new',
        'title' => 'Imported from remote',
        'sync_status' => 'synced',
    ]);

    $conflict = CalendarEvent::query()->where('external_id', 'remote_evt_conflict')->first();

    expect($conflict)->not->toBeNull();
    expect($conflict?->title)->toBe('Local updated title');
    expect($conflict?->sync_status)->toBe('conflict');

    Carbon::setTestNow();
});
