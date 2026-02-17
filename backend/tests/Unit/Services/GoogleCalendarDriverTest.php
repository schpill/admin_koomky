<?php

use App\Models\CalendarConnection;
use App\Services\Calendar\GoogleCalendarDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('google calendar driver fetches pushes and deletes events', function () {
    $connection = CalendarConnection::factory()->create([
        'provider' => 'google',
        'calendar_id' => 'primary',
        'credentials' => [
            'access_token' => 'google-access-token',
            'refresh_token' => 'google-refresh-token',
        ],
    ]);

    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*/events?*' => Http::response([
            'items' => [
                [
                    'id' => 'google_evt_1',
                    'summary' => 'Demo meeting',
                    'description' => 'Kickoff',
                    'start' => ['dateTime' => '2026-03-01T09:00:00Z'],
                    'end' => ['dateTime' => '2026-03-01T10:00:00Z'],
                    'updated' => '2026-03-01T08:00:00Z',
                    'location' => 'Remote',
                ],
            ],
        ], 200),
        'https://www.googleapis.com/calendar/v3/calendars/*/events' => Http::response([
            'id' => 'google_evt_2',
            'updated' => '2026-03-02T08:00:00Z',
        ], 200),
        'https://www.googleapis.com/calendar/v3/calendars/*/events/*' => Http::response('', 204),
    ]);

    $driver = new GoogleCalendarDriver();

    $events = $driver->fetchEvents($connection, [
        'from' => '2026-03-01T00:00:00Z',
        'to' => '2026-03-31T23:59:59Z',
    ]);

    expect($events)->toHaveCount(1);
    expect($events[0]['external_id'])->toBe('google_evt_1');
    expect($events[0]['title'])->toBe('Demo meeting');

    $pushed = $driver->pushEvent($connection, [
        'title' => 'Client call',
        'description' => 'Quarterly review',
        'start_at' => '2026-03-03T14:00:00Z',
        'end_at' => '2026-03-03T15:00:00Z',
        'location' => 'Paris',
    ]);

    expect($pushed['external_id'])->toBe('google_evt_2');
    expect($driver->deleteEvent($connection, 'google_evt_2'))->toBeTrue();
});

test('google calendar driver refreshes token on 401', function () {
    $connection = CalendarConnection::factory()->create([
        'provider' => 'google',
        'calendar_id' => 'primary',
        'credentials' => [
            'access_token' => 'expired-token',
            'refresh_token' => 'refresh-token',
        ],
    ]);

    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*/events?*' => Http::sequence()
            ->push([], 401)
            ->push(['items' => []], 200),
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'new-access-token',
        ], 200),
    ]);

    $driver = new GoogleCalendarDriver();
    $events = $driver->fetchEvents($connection, [
        'from' => '2026-03-01T00:00:00Z',
        'to' => '2026-03-31T23:59:59Z',
    ]);

    $connection->refresh();

    expect($events)->toBeArray();
    expect(data_get($connection->credentials, 'access_token'))->toBe('new-access-token');
});
