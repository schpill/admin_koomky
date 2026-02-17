<?php

namespace App\Jobs;

use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Services\Calendar\CalendarSyncManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncCalendarJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $connectionId) {}

    public function handle(?CalendarSyncManager $calendarSyncManager = null): void
    {
        $calendarSyncManager ??= app(CalendarSyncManager::class);

        $connection = CalendarConnection::query()->with('user')->find($this->connectionId);
        if (! $connection || ! $connection->sync_enabled) {
            return;
        }

        try {
            $events = $calendarSyncManager->fetchEvents($connection, [
                'from' => now()->subDays(30)->toIso8601String(),
                'to' => now()->addDays(90)->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('calendar_sync_fetch_failed', [
                'connection_id' => $connection->id,
                'provider' => $connection->provider,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        foreach ($events as $payload) {
            $externalId = (string) ($payload['external_id'] ?? '');
            if ($externalId === '') {
                continue;
            }

            $remoteUpdatedAt = $this->parseDateTime($payload['external_updated_at'] ?? null);

            $existing = CalendarEvent::query()
                ->where('user_id', $connection->user_id)
                ->where('calendar_connection_id', $connection->id)
                ->where('external_id', $externalId)
                ->first();

            if ($existing && $existing->isLocallyNewerThan($remoteUpdatedAt)) {
                $existing->update(['sync_status' => 'conflict']);

                continue;
            }

            CalendarEvent::query()->updateOrCreate(
                [
                    'user_id' => $connection->user_id,
                    'calendar_connection_id' => $connection->id,
                    'external_id' => $externalId,
                ],
                [
                    'title' => (string) ($payload['title'] ?? 'Untitled event'),
                    'description' => $payload['description'] ?? null,
                    'start_at' => $this->parseDateTime($payload['start_at'] ?? null) ?? now(),
                    'end_at' => $this->parseDateTime($payload['end_at'] ?? null) ?? now()->addHour(),
                    'all_day' => (bool) ($payload['all_day'] ?? false),
                    'location' => $payload['location'] ?? null,
                    'type' => $this->safeType($payload['type'] ?? null),
                    'sync_status' => 'synced',
                    'external_updated_at' => $remoteUpdatedAt,
                ]
            );
        }

        $connection->update(['last_synced_at' => now()]);
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function safeType(mixed $value): string
    {
        $type = is_string($value) ? $value : 'custom';
        $allowed = ['meeting', 'deadline', 'reminder', 'task', 'custom'];

        return in_array($type, $allowed, true) ? $type : 'custom';
    }
}
