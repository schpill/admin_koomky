<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use InvalidArgumentException;

class CalendarSyncManager
{
    public function __construct(
        private readonly GoogleCalendarDriver $googleCalendarDriver,
        private readonly CalDavDriver $calDavDriver,
    ) {}

    /**
     * @param  array<string, string>  $dateRange
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(CalendarConnection $connection, array $dateRange): array
    {
        return $this->driverFor($connection)->fetchEvents($connection, $dateRange);
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function pushEvent(CalendarConnection $connection, array $event): array
    {
        return $this->driverFor($connection)->pushEvent($connection, $event);
    }

    public function deleteEvent(CalendarConnection $connection, string $eventId): bool
    {
        return $this->driverFor($connection)->deleteEvent($connection, $eventId);
    }

    private function driverFor(CalendarConnection $connection): CalendarSyncService
    {
        return match ($connection->provider) {
            'google' => $this->googleCalendarDriver,
            'caldav' => $this->calDavDriver,
            default => throw new InvalidArgumentException('Unsupported calendar provider: '.$connection->provider),
        };
    }
}
