<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CalDavDriver implements CalendarSyncService
{
    /**
     * @param  array<string, string>  $dateRange
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(CalendarConnection $connection, array $dateRange): array
    {
        $response = $this->request($connection, 'GET', $this->baseUrl($connection).'/events', [
            'from' => $dateRange['from'] ?? now()->startOfMonth()->toIso8601String(),
            'to' => $dateRange['to'] ?? now()->endOfMonth()->toIso8601String(),
            'calendar_id' => $connection->calendar_id,
        ]);

        $items = $response->json('items', []);
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_map(function (mixed $item): array {
            return [
                'external_id' => (string) data_get($item, 'id'),
                'title' => (string) (data_get($item, 'title') ?: 'Untitled event'),
                'description' => data_get($item, 'description'),
                'start_at' => Carbon::parse((string) data_get($item, 'start_at'))->toDateTimeString(),
                'end_at' => Carbon::parse((string) data_get($item, 'end_at'))->toDateTimeString(),
                'all_day' => (bool) data_get($item, 'all_day', false),
                'location' => data_get($item, 'location'),
                'external_updated_at' => data_get($item, 'updated_at'),
            ];
        }, $items));
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function pushEvent(CalendarConnection $connection, array $event): array
    {
        $response = $this->request(
            $connection,
            'POST',
            $this->baseUrl($connection).'/events',
            [],
            [
                'calendar_id' => $connection->calendar_id,
                'title' => $event['title'] ?? 'Untitled event',
                'description' => $event['description'] ?? null,
                'start_at' => Carbon::parse((string) ($event['start_at'] ?? now()))->toIso8601String(),
                'end_at' => Carbon::parse((string) ($event['end_at'] ?? now()->addHour()))->toIso8601String(),
                'location' => $event['location'] ?? null,
            ]
        );

        return [
            'external_id' => (string) ($response->json('id') ?? ''),
            'external_updated_at' => $response->json('updated_at'),
        ];
    }

    public function deleteEvent(CalendarConnection $connection, string $eventId): bool
    {
        $response = $this->request($connection, 'DELETE', $this->baseUrl($connection).'/events/'.$eventId);

        return $response->successful() || $response->status() === 204;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     */
    private function request(
        CalendarConnection $connection,
        string $method,
        string $url,
        array $query = [],
        array $payload = [],
    ): \Illuminate\Http\Client\Response {
        $username = (string) data_get($connection->credentials, 'username', '');
        $password = (string) data_get($connection->credentials, 'password', '');

        if ($username === '' || $password === '') {
            throw new RuntimeException('Missing CalDAV credentials');
        }

        $response = Http::timeout(20)
            ->withBasicAuth($username, $password)
            ->send($method, $url, [
                'query' => $query,
                'json' => $payload,
            ]);

        if ($response->status() === 401) {
            throw new RuntimeException('CalDAV authentication failed');
        }

        if (! $response->successful()) {
            throw new RuntimeException('CalDAV request failed: '.$response->status());
        }

        return $response;
    }

    private function baseUrl(CalendarConnection $connection): string
    {
        $url = (string) data_get($connection->credentials, 'base_url', '');
        if ($url === '') {
            throw new RuntimeException('Missing CalDAV base URL');
        }

        return rtrim($url, '/');
    }
}
