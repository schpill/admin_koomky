<?php

use App\Models\CalendarConnection;
use App\Services\Calendar\CalDavDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('caldav driver fetches pushes and deletes events', function () {
    $connection = CalendarConnection::factory()->create([
        'provider' => 'caldav',
        'calendar_id' => 'personal',
        'credentials' => [
            'base_url' => 'https://caldav.example.test',
            'username' => 'alice',
            'password' => 'secret',
        ],
    ]);

    Http::fake([
        'https://caldav.example.test/events?*' => Http::response([
            'items' => [
                [
                    'id' => 'caldav_evt_1',
                    'title' => 'CalDAV standup',
                    'description' => 'Daily sync',
                    'start_at' => '2026-03-03T08:00:00Z',
                    'end_at' => '2026-03-03T08:30:00Z',
                    'location' => 'Office',
                    'updated_at' => '2026-03-03T07:55:00Z',
                ],
            ],
        ], 200),
        'https://caldav.example.test/events' => Http::response([
            'id' => 'caldav_evt_2',
            'updated_at' => '2026-03-03T10:00:00Z',
        ], 201),
        'https://caldav.example.test/events/*' => Http::response('', 204),
    ]);

    $driver = new CalDavDriver;

    $events = $driver->fetchEvents($connection, [
        'from' => '2026-03-01T00:00:00Z',
        'to' => '2026-03-31T23:59:59Z',
    ]);

    expect($events)->toHaveCount(1);
    expect($events[0]['external_id'])->toBe('caldav_evt_1');

    $pushed = $driver->pushEvent($connection, [
        'title' => 'Client follow-up',
        'description' => 'Status update',
        'start_at' => '2026-03-04T09:00:00Z',
        'end_at' => '2026-03-04T10:00:00Z',
        'location' => 'Remote',
    ]);

    expect($pushed['external_id'])->toBe('caldav_evt_2');
    expect($driver->deleteEvent($connection, 'caldav_evt_2'))->toBeTrue();
});

test('caldav driver throws on authentication failure', function () {
    $connection = CalendarConnection::factory()->create([
        'provider' => 'caldav',
        'credentials' => [
            'base_url' => 'https://caldav.example.test',
            'username' => 'alice',
            'password' => 'secret',
        ],
    ]);

    Http::fake([
        'https://caldav.example.test/events?*' => Http::response([], 401),
    ]);

    $driver = new CalDavDriver;

    expect(fn () => $driver->fetchEvents($connection, [
        'from' => '2026-03-01T00:00:00Z',
        'to' => '2026-03-31T23:59:59Z',
    ]))->toThrow(\RuntimeException::class);
});
