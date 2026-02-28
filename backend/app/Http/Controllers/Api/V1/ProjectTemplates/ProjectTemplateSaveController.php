<?php

namespace App\Http\Controllers\Api\V1\ProjectTemplates;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProjectTemplateSaveController extends Controller
{
    public function __construct(
        protected ProjectTemplateService $templateService,
    ) {}

    /**
     * Save a project as a template.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $template = $this->templateService->createFromProject(
            $project,
            $validated['name'],
            $validated['description'] ?? null
        );

        $template->load('templateTasks');

        return response()->json([
            'data' => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'tasks_count' => $template->templateTasks->count(),
            ],
        ], Response::HTTP_CREATED);
    }
}