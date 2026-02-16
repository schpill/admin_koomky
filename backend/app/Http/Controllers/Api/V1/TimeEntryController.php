<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tasks\StoreTimeEntryRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TimeEntryController extends Controller
{
    use ApiResponse;

    public function store(StoreTimeEntryRequest $request, Project $project, Task $task): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskBelongsToProject($project, $task);

        /** @var User $user */
        $user = $request->user();

        $timeEntry = TimeEntry::query()->create([
            ...$request->validated(),
            'user_id' => $user->id,
            'task_id' => $task->id,
        ]);

        return $this->success($timeEntry, 'Time entry created successfully', 201);
    }

    public function update(StoreTimeEntryRequest $request, Project $project, Task $task, TimeEntry $timeEntry): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskBelongsToProject($project, $task);
        $this->ensureTimeEntryBelongsToTask($task, $timeEntry);

        $timeEntry->update($request->validated());

        return $this->success($timeEntry, 'Time entry updated successfully');
    }

    public function destroy(Project $project, Task $task, TimeEntry $timeEntry): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskBelongsToProject($project, $task);
        $this->ensureTimeEntryBelongsToTask($task, $timeEntry);

        $timeEntry->delete();

        return $this->success(null, 'Time entry deleted successfully');
    }

    private function ensureTaskBelongsToProject(Project $project, Task $task): void
    {
        if ($task->project_id !== $project->id) {
            abort(404, 'Task not found for this project');
        }
    }

    private function ensureTimeEntryBelongsToTask(Task $task, TimeEntry $timeEntry): void
    {
        if ($timeEntry->task_id !== $task->id) {
            abort(404, 'Time entry not found for this task');
        }
    }
}
