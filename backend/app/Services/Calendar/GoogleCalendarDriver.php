<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendarDriver implements CalendarSyncService
{
    public function __construct(
        private readonly string $baseUrl = 'https://www.googleapis.com/calendar/v3',
    ) {}

    /**
     * @param  array<string, string>  $dateRange
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(CalendarConnection $connection, array $dateRange): array
    {
        $response = $this->authorizedRequest($connection, 'GET', $this->eventsUrl($connection), [
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'timeMin' => $dateRange['from'] ?? now()->startOfMonth()->toIso8601String(),
            'timeMax' => $dateRange['to'] ?? now()->endOfMonth()->toIso8601String(),
        ]);

        $items = $response->json('items', []);
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_map(function (mixed $item): array {
            $startDateTime = data_get($item, 'start.dateTime');
            $startDate = data_get($item, 'start.date');
            $endDateTime = data_get($item, 'end.dateTime');
            $endDate = data_get($item, 'end.date');

            $allDay = is_string($startDate) && ! is_string($startDateTime);

            return [
                'external_id' => (string) data_get($item, 'id'),
                'title' => (string) (data_get($item, 'summary') ?: 'Untitled event'),
                'description' => data_get($item, 'description'),
                'start_at' => $allDay
                    ? Carbon::parse((string) $startDate)->startOfDay()->toDateTimeString()
                    : Carbon::parse((string) $startDateTime)->toDateTimeString(),
                'end_at' => $allDay
                    ? Carbon::parse((string) ($endDate ?: $startDate))->endOfDay()->toDateTimeString()
                    : Carbon::parse((string) $endDateTime)->toDateTimeString(),
                'all_day' => $allDay,
                'location' => data_get($item, 'location'),
                'external_updated_at' => data_get($item, 'updated'),
            ];
        }, $items));
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function pushEvent(CalendarConnection $connection, array $event): array
    {
        $payload = [
            'summary' => (string) ($event['title'] ?? 'Untitled event'),
            'description' => $event['description'] ?? null,
            'location' => $event['location'] ?? null,
            'start' => ['dateTime' => Carbon::parse((string) ($event['start_at'] ?? now()))->toIso8601String()],
            'end' => ['dateTime' => Carbon::parse((string) ($event['end_at'] ?? now()->addHour()))->toIso8601String()],
        ];

        $response = $this->authorizedRequest($connection, 'POST', $this->eventsUrl($connection), [], $payload);

        return [
            'external_id' => (string) ($response->json('id') ?? ''),
            'external_updated_at' => $response->json('updated'),
        ];
    }

    public function deleteEvent(CalendarConnection $connection, string $eventId): bool
    {
        $response = $this->authorizedRequest($connection, 'DELETE', $this->eventsUrl($connection).'/'.$eventId);

        return $response->successful() || $response->status() === 204;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     */
    private function authorizedRequest(
        CalendarConnection $connection,
        string $method,
        string $url,
        array $query = [],
        array $payload = [],
    ): Response {
        $accessToken = (string) data_get($connection->credentials, 'access_token', '');
        if ($accessToken === '') {
            throw new RuntimeException('Missing Google access token');
        }

        $client = Http::timeout(20)->withToken($accessToken);
        $response = $client->send($method, $url, [
            'query' => $query,
            'json' => $payload,
        ]);

        if ($response->status() === 401 && $this->refreshToken($connection)) {
            $newAccessToken = (string) data_get($connection->credentials, 'access_token', '');
            $response = Http::timeout(20)
                ->withToken($newAccessToken)
                ->send($method, $url, [
                    'query' => $query,
                    'json' => $payload,
                ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Google Calendar request failed: '.$response->status());
        }

        return $response;
    }

    private function refreshToken(CalendarConnection $connection): bool
    {
        $refreshToken = (string) data_get($connection->credentials, 'refresh_token', '');
        if ($refreshToken === '') {
            return false;
        }

        $response = Http::timeout(20)->asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => (string) config('services.google.client_id'),
            'client_secret' => (string) config('services.google.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            return false;
        }

        $newToken = (string) $response->json('access_token', '');
        if ($newToken === '') {
            return false;
        }

        $credentials = (array) $connection->credentials;
        $credentials['access_token'] = $newToken;
        $connection->update(['credentials' => $credentials]);
        $connection->refresh();

        return true;
    }

    private function eventsUrl(CalendarConnection $connection): string
    {
        $calendarId = urlencode((string) ($connection->calendar_id ?: 'primary'));

        return "{$this->baseUrl}/calendars/{$calendarId}/events";
    }
}
