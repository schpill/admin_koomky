<?php

namespace App\Http\Controllers\Api\V1\Timer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Timer\StoreLiveTimerRequest;
use App\Models\Task;
use App\Services\LiveTimerService;
use App\Services\WebhookDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LiveTimerController extends Controller
{
    public function __construct(
        protected LiveTimerService $timerService,
        protected WebhookDispatchService $webhookService,
    ) {}

    /**
     * Get the currently active timer for the authenticated user.
     */
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeEntry = $this->timerService->active($user);

        if (! $activeEntry) {
            return response()->json(['message' => 'No active timer'], Response::HTTP_NO_CONTENT);
        }

        $activeEntry->load(['task', 'task.project']);

        return response()->json([
            'data' => [
                'id' => $activeEntry->id,
                'task_id' => $activeEntry->task_id,
                'task_name' => $activeEntry->task->title,
                'project_id' => $activeEntry->task->project->id,
                'project_name' => $activeEntry->task->project->name,
                'started_at' => $activeEntry->started_at->toIso8601String(),
                'description' => $activeEntry->description,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Start a new timer for a task.
     */
    public function start(StoreLiveTimerRequest $request): JsonResponse
    {
        $user = $request->user();
        $task = Task::findOrFail($request->validated('task_id'));

        // Check project ownership via policy
        $this->authorize('update', $task->project);

        $timeEntry = $this->timerService->start(
            $user,
            $task,
            $request->validated('description')
        );

        $timeEntry->load(['task', 'task.project']);

        return response()->json([
            'data' => [
                'id' => $timeEntry->id,
                'task_id' => $timeEntry->task_id,
                'task_name' => $timeEntry->task->title,
                'project_id' => $timeEntry->task->project->id,
                'project_name' => $timeEntry->task->project->name,
                'started_at' => $timeEntry->started_at->toIso8601String(),
                'description' => $timeEntry->description,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Stop the current timer and create a time entry.
     */
    public function stop(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeEntry = $this->timerService->active($user);

        if (! $activeEntry) {
            return response()->json([
                'message' => 'No active timer to stop.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $timeEntry = $this->timerService->stop($user);
        $timeEntry->load(['task', 'task.project']);

        // Dispatch webhook
        $this->webhookService->dispatch('time.timer_stopped', [
            'task_id' => $timeEntry->task_id,
            'project_id' => $timeEntry->task->project->id,
            'duration_minutes' => $timeEntry->duration_minutes,
            'date' => $timeEntry->date->toDateString(),
            'started_at' => $timeEntry->started_at->toIso8601String(),
            'stopped_at' => now()->toIso8601String(),
        ], $user->id);

        return response()->json([
            'data' => [
                'id' => $timeEntry->id,
                'task_id' => $timeEntry->task_id,
                'task_name' => $timeEntry->task->title,
                'project_id' => $timeEntry->task->project->id,
                'project_name' => $timeEntry->task->project->name,
                'duration_minutes' => $timeEntry->duration_minutes,
                'date' => $timeEntry->date->toDateString(),
                'description' => $timeEntry->description,
                'started_at' => $timeEntry->started_at->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Cancel the current timer without creating a time entry.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->timerService->cancel($user);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
