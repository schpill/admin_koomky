<?php

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\CalendarEventReminderNotification;

class CalendarAutoEventService
{
    public function syncProjectDeadline(Project $project): void
    {
        if (! $project->deadline) {
            return;
        }

        $event = CalendarEvent::query()->updateOrCreate(
            [
                'user_id' => $project->user_id,
                'eventable_type' => Project::class,
                'eventable_id' => $project->id,
                'type' => 'deadline',
            ],
            [
                'title' => 'Project deadline: '.$project->name,
                'description' => 'Auto-created from project deadline',
                'start_at' => $project->deadline->copy()->startOfDay(),
                'end_at' => $project->deadline->copy()->endOfDay(),
                'all_day' => true,
                'location' => null,
                'sync_status' => 'local',
            ]
        );

        $this->notify($project->user, $event);
    }

    public function syncTaskDueDate(Task $task): void
    {
        if (! $task->due_date || ! $task->project) {
            return;
        }

        $event = CalendarEvent::query()->updateOrCreate(
            [
                'user_id' => $task->project->user_id,
                'eventable_type' => Task::class,
                'eventable_id' => $task->id,
                'type' => 'task',
            ],
            [
                'title' => 'Task due: '.$task->title,
                'description' => $task->description,
                'start_at' => $task->due_date->copy()->startOfDay(),
                'end_at' => $task->due_date->copy()->endOfDay(),
                'all_day' => true,
                'location' => null,
                'sync_status' => 'local',
            ]
        );

        $this->notify($task->project->user, $event);
    }

    public function syncInvoiceReminder(Invoice $invoice): void
    {
        if (! $invoice->due_date) {
            return;
        }

        $reminderAt = $invoice->due_date->copy()->subDays(3)->startOfDay();

        $event = CalendarEvent::query()->updateOrCreate(
            [
                'user_id' => $invoice->user_id,
                'eventable_type' => Invoice::class,
                'eventable_id' => $invoice->id,
                'type' => 'reminder',
            ],
            [
                'title' => 'Invoice reminder: '.$invoice->number,
                'description' => 'Auto-created reminder for invoice due date',
                'start_at' => $reminderAt,
                'end_at' => $reminderAt->copy()->addHour(),
                'all_day' => false,
                'location' => null,
                'sync_status' => 'local',
            ]
        );

        $this->notify($invoice->user, $event);
    }

    private function notify(mixed $user, CalendarEvent $event): void
    {
        if (! $user) {
            return;
        }

        $user->notify(new CalendarEventReminderNotification($event));
    }
}
