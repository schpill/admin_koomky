<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\Calendar\CalendarAutoEventService;

class TaskObserver
{
    public function created(Task $task): void
    {
        app(CalendarAutoEventService::class)->syncTaskDueDate($task);
    }

    public function updated(Task $task): void
    {
        if (! $task->wasChanged(['due_date', 'title', 'description'])) {
            return;
        }

        app(CalendarAutoEventService::class)->syncTaskDueDate($task);
    }
}
