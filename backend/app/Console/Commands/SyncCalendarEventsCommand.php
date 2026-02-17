<?php

namespace App\Console\Commands;

use App\Jobs\SyncCalendarJob;
use App\Models\CalendarConnection;
use Illuminate\Console\Command;

class SyncCalendarEventsCommand extends Command
{
    protected $signature = 'calendar:sync';

    protected $description = 'Synchronize events for all active calendar connections';

    public function handle(): int
    {
        $connectionIds = CalendarConnection::query()
            ->enabled()
            ->pluck('id')
            ->all();

        foreach ($connectionIds as $connectionId) {
            SyncCalendarJob::dispatch($connectionId);
        }

        $this->info('Calendar sync jobs dispatched: '.count($connectionIds));

        return self::SUCCESS;
    }
}
