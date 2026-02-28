<?php

namespace App\Http\Controllers\Api\V1\ProjectTemplates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProjectTemplates\StoreProjectTemplateRequest;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectTemplateController extends Controller
{
    /**
     * List all project templates for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $templates = ProjectTemplate::where('user_id', $request->user()->id)
            ->with('templateTasks')
            ->withCount('templateTasks')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => $templates->getCollection()
                ->map(fn (ProjectTemplate $template) => $this->formatTemplate($template))
                ->values()
                ->all(),
            'current_page' => $templates->currentPage(),
            'last_page' => $templates->lastPage(),
            'per_page' => $templates->perPage(),
            'total' => $templates->total(),
        ]);
    }

    /**
     * Create a new project template.
     */
    public function store(StoreProjectTemplateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $template = ProjectTemplate::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'billing_type' => $validated['billing_type'] ?? null,
            'default_hourly_rate' => $validated['default_hourly_rate'] ?? null,
            'default_currency' => $validated['default_currency'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
        ]);

        // Create template tasks if provided
        if (! empty($validated['tasks'])) {
            foreach ($validated['tasks'] as $index => $taskData) {
                ProjectTemplateTask::create([
                    'template_id' => $template->id,
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? null,
                    'estimated_hours' => $taskData['estimated_hours'] ?? null,
                    'priority' => $taskData['priority'] ?? 'medium',
                    'sort_order' => $taskData['sort_order'] ?? $index,
                ]);
            }
        }

        $template->load('templateTasks');

        return response()->json([
            'data' => $this->formatTemplate($template),
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single project template.
     */
    public function show(ProjectTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $template->load('templateTasks');

        return response()->json([
            'data' => $this->formatTemplate($template),
        ]);
    }

    /**
     * Update a project template.
     */
    public function update(StoreProjectTemplateRequest $request, ProjectTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $validated = $request->validated();

        $template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'billing_type' => $validated['billing_type'] ?? null,
            'default_hourly_rate' => $validated['default_hourly_rate'] ?? null,
            'default_currency' => $validated['default_currency'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
        ]);

        // Update tasks if provided - delete existing and create new
        if (isset($validated['tasks'])) {
            $template->templateTasks()->delete();

            foreach ($validated['tasks'] as $index => $taskData) {
                ProjectTemplateTask::create([
                    'template_id' => $template->id,
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? null,
                    'estimated_hours' => $taskData['estimated_hours'] ?? null,
                    'priority' => $taskData['priority'] ?? 'medium',
                    'sort_order' => $taskData['sort_order'] ?? $index,
                ]);
            }
        }

        $template->load('templateTasks');

        return response()->json([
            'data' => $this->formatTemplate($template),
        ]);
    }

    /**
     * Delete a project template.
     */
    public function destroy(ProjectTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Duplicate a project template.
     */
    public function duplicate(ProjectTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $newTemplate = ProjectTemplate::create([
            'user_id' => $template->user_id,
            'name' => $template->name.' (Copie)',
            'description' => $template->description,
            'billing_type' => $template->billing_type,
            'default_hourly_rate' => $template->default_hourly_rate,
            'default_currency' => $template->default_currency,
            'estimated_hours' => $template->estimated_hours,
        ]);

        // Copy tasks
        foreach ($template->templateTasks()->ordered()->get() as $task) {
            ProjectTemplateTask::create([
                'template_id' => $newTemplate->id,
                'title' => $task->title,
                'description' => $task->description,
                'estimated_hours' => $task->estimated_hours,
                'priority' => $task->priority,
                'sort_order' => $task->sort_order,
            ]);
        }

        $newTemplate->load('templateTasks');

        return response()->json([
            'data' => $this->formatTemplate($newTemplate),
        ], Response::HTTP_CREATED);
    }

    /**
     * Format template for JSON response.
     *
     * @return array<string, mixed>
     */
    private function formatTemplate(ProjectTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'billing_type' => $template->billing_type,
            'default_hourly_rate' => $template->default_hourly_rate,
            'default_currency' => $template->default_currency,
            'estimated_hours' => $template->estimated_hours,
            'tasks_count' => $template->templateTasks->count(),
            'created_at' => $template->created_at->toIso8601String(),
            'tasks' => $template->templateTasks->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'estimated_hours' => $task->estimated_hours,
                'priority' => $task->priority,
                'sort_order' => $task->sort_order,
            ])->toArray(),
        ];
    }
}
