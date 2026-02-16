<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tasks\StoreTaskRequest;
use App\Http\Requests\Api\V1\Tasks\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    use ApiResponse;

    public function index(Project $project, Request $request): JsonResponse
    {
        Gate::authorize('view', $project);

        $query = $project->tasks()->with(['dependencies', 'attachments'])->orderBy('sort_order');

        if ($request->filled('status') && is_string($request->input('status'))) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('priority') && is_string($request->input('priority'))) {
            $query->byPriority($request->input('priority'));
        }

        return $this->success($query->get(), 'Tasks retrieved successfully');
    }

    public function show(Project $project, Task $task): JsonResponse
    {
        Gate::authorize('view', $project);
        $this->ensureTaskBelongsToProject($project, $task);

        $task->load(['dependencies', 'attachments', 'timeEntries']);

        return $this->success($task, 'Task retrieved successfully');
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validated();
        $data['project_id'] = $project->id;
        $data['status'] = $data['status'] ?? 'todo';
        $data['priority'] = $data['priority'] ?? 'medium';
        $data['sort_order'] = ($project->tasks()->max('sort_order') ?? -1) + 1;

        $task = Task::query()->create($data);

        return $this->success($task->load(['dependencies', 'attachments']), 'Task created successfully', 201);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskBelongsToProject($project, $task);

        $data = $request->validated();

        if (
            ($data['status'] ?? null) === 'in_progress'
            && $task->status !== 'in_progress'
            && ! $task->canTransitionTo('in_progress')
        ) {
            return $this->error('Task dependencies must be completed before starting this task', 422);
        }

        $task->update($data);

        return $this->success($task->fresh(['dependencies', 'attachments']), 'Task updated successfully');
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskBelongsToProject($project, $task);

        $task->delete();

        return $this->success(null, 'Task deleted successfully');
    }

    public function reorder(Project $project, Request $request): JsonResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['required', 'uuid'],
        ]);

        /** @var list<string> $taskIds */
        $taskIds = $validated['task_ids'];

        $projectTaskCount = $project->tasks()->whereIn('id', $taskIds)->count();
        if ($projectTaskCount !== count($taskIds)) {
            return $this->error('One or more tasks do not belong to this project', 422);
        }

        DB::transaction(function () use ($taskIds): void {
            foreach ($taskIds as $index => $taskId) {
                Task::query()->where('id', $taskId)->update(['sort_order' => $index]);
            }
        });

        return $this->success(null, 'Tasks reordered successfully');
    }

    public function addDependency(Project $project, Task $task, Request $request): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskBelongsToProject($project, $task);

        $validated = $request->validate([
            'depends_on_task_id' => ['required', 'uuid', 'exists:tasks,id'],
        ]);

        $dependsOnTaskId = (string) $validated['depends_on_task_id'];
        $dependsOnTask = Task::query()->findOrFail($dependsOnTaskId);

        if ($dependsOnTask->project_id !== $project->id) {
            return $this->error('Dependency task must belong to the same project', 422);
        }

        if ($task->hasCircularDependency($dependsOnTaskId)) {
            return $this->error('Circular dependency detected', 422);
        }

        $task->dependencies()->syncWithoutDetaching([$dependsOnTaskId]);

        return $this->success($task->fresh('dependencies'), 'Dependency added successfully');
    }

    public function uploadAttachment(Project $project, Task $task, Request $request): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskBelongsToProject($project, $task);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $validated['file'];
        if (! $file instanceof \Illuminate\Http\UploadedFile) {
            return $this->error('Invalid uploaded file', 422);
        }

        $currentSize = (int) $task->attachments()->sum('size_bytes');
        $incomingSize = (int) $file->getSize();

        if (($currentSize + $incomingSize) > (50 * 1024 * 1024)) {
            return $this->error('Total attachment size limit exceeded for this task', 422);
        }

        $storedPath = Storage::disk('attachments')->putFile('tasks/'.$task->id, $file);

        if (! is_string($storedPath) || $storedPath === '') {
            return $this->error('Unable to store attachment', 500);
        }

        $attachment = TaskAttachment::query()->create([
            'task_id' => $task->id,
            'filename' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $incomingSize,
        ]);

        return $this->success($attachment, 'Attachment uploaded successfully', 201);
    }

    public function downloadAttachment(Project $project, Task $task, TaskAttachment $attachment): StreamedResponse|JsonResponse
    {
        Gate::authorize('view', $project);
        $this->ensureTaskBelongsToProject($project, $task);

        if ($attachment->task_id !== $task->id) {
            return $this->error('Attachment not found for this task', 404);
        }

        if (! Storage::disk('attachments')->exists($attachment->path)) {
            return $this->error('Attachment file not found', 404);
        }

        return Storage::disk('attachments')->download($attachment->path, $attachment->filename);
    }

    public function deleteAttachment(Project $project, Task $task, TaskAttachment $attachment): JsonResponse
    {
        Gate::authorize('update', $project);
        $this->ensureTaskBelongsToProject($project, $task);

        if ($attachment->task_id !== $task->id) {
            return $this->error('Attachment not found for this task', 404);
        }

        Storage::disk('attachments')->delete($attachment->path);
        $attachment->delete();

        return $this->success(null, 'Attachment deleted successfully');
    }

    private function ensureTaskBelongsToProject(Project $project, Task $task): void
    {
        if ($task->project_id !== $project->id) {
            abort(404, 'Task not found for this project');
        }
    }
}
