<?php

namespace App\Http\Controllers\Api\V1\ProjectTemplates;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ProjectTemplate;
use App\Services\ProjectTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProjectTemplateInstantiateController extends Controller
{
    public function __construct(
        protected ProjectTemplateService $templateService,
    ) {}

    /**
     * Instantiate a project from a template.
     */
    public function store(Request $request, ProjectTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $user = $request->user();
        assert($user instanceof \App\Models\User);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('clients', 'id')->where(
                    fn ($query) => $query->where('user_id', $user->id)
                ),
            ],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $client = Client::query()->findOrFail($validated['client_id']);
        assert($client instanceof Client);

        $project = $this->templateService->instantiate(
            $template,
            $validated,
            $user
        );

        $project->load('tasks');

        return response()->json([
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'client_id' => $project->client_id,
                'client_name' => $client->name,
                'status' => $project->status,
                'tasks_count' => $project->tasks->count(),
            ],
        ], Response::HTTP_CREATED);
    }
}
