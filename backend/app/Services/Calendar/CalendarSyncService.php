<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;

interface CalendarSyncService
{
    /**
     * @param  array<string, string>  $dateRange
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(CalendarConnection $connection, array $dateRange): array;

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function pushEvent(CalendarConnection $connection, array $event): array;

    public function deleteEvent(CalendarConnection $connection, string $eventId): bool;
}
