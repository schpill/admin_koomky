<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LiveTimerService
{
    /**
     * Start a new timer for the given task.
     * Stops any existing running timer first.
     */
    public function start(User $user, Task $task, ?string $description = null): TimeEntry
    {
        // Stop any existing running timer for this user
        $this->stop($user);

        $timeEntry = TimeEntry::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'started_at' => now(),
            'is_running' => true,
            'duration_minutes' => 0,
            'date' => now()->toDateString(),
            'description' => $description,
        ]);

        return $timeEntry;
    }

    /**
     * Stop the current running timer and create a time entry.
     *
     * @throws \RuntimeException If no running timer exists
     */
    public function stop(User $user): TimeEntry
    {
        $activeEntry = $this->active($user);

        if (!$activeEntry) {
            throw new \RuntimeException('No active timer found for this user.');
        }

        $durationMinutes = $activeEntry->computeDurationMinutes();

        $activeEntry->update([
            'is_running' => false,
            'duration_minutes' => max(1, $durationMinutes),
            'date' => $activeEntry->started_at->toDateString(),
        ]);

        return $activeEntry->fresh();
    }

    /**
     * Cancel the current running timer without creating a time entry.
     */
    public function cancel(User $user): void
    {
        $activeEntry = $this->active($user);

        if ($activeEntry) {
            $activeEntry->delete();
        }
    }

    /**
     * Get the currently active timer for a user.
     */
    public function active(User $user): ?TimeEntry
    {
        return TimeEntry::where('user_id', $user->id)
            ->running()
            ->first();
    }
}