<?php

namespace App\Console\Commands;

use App\Services\ReminderDispatchService;
use Illuminate\Console\Command;

class DispatchDueRemindersCommand extends Command
{
    protected $signature = 'reminders:dispatch';

    protected $description = 'Dispatch due reminder jobs';

    public function handle(ReminderDispatchService $service): int
    {
        $count = $service->dispatchDue();

        $this->info("{$count} relances dispatchées.");

        return self::SUCCESS;
    }
}
