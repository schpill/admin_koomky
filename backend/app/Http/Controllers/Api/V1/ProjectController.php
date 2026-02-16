<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use App\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use App\Http\Resources\Api\V1\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Services\ReferenceGenerator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Project::query()
            ->where('user_id', $user->id)
            ->with(['client'])
            ->withCount('tasks');

        if ($request->filled('status') && is_string($request->input('status'))) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('client_id') && is_string($request->input('client_id'))) {
            $query->byClient($request->input('client_id'));
        }

        if ($request->filled('date_from') && is_string($request->input('date_from'))) {
            $query->whereDate('start_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to') && is_string($request->input('date_to'))) {
            $query->whereDate('deadline', '<=', $request->input('date_to'));
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['reference', 'name', 'status', 'deadline', 'created_at'];
        if (is_string($sortField) && in_array($sortField, $allowedSortFields, true)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $projects = $query->paginate((int) ($request->input('per_page', 15)));
        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = ProjectResource::collection($projects)->response()->getData(true);

        $data = [
            'data' => $collectionPayload['data'] ?? [],
            'current_page' => $projects->currentPage(),
            'per_page' => $projects->perPage(),
            'total' => $projects->total(),
            'last_page' => $projects->lastPage(),
        ];

        return $this->success($data, 'Projects retrieved successfully');
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['reference'] = ReferenceGenerator::generate('projects', 'PRJ');
        $data['status'] = $data['status'] ?? 'draft';

        if ($data['billing_type'] === 'hourly') {
            $data['fixed_price'] = null;
        }

        if ($data['billing_type'] === 'fixed') {
            $data['hourly_rate'] = null;
        }

        $project = Project::query()->create($data);
        $project->load('client');

        return $this->success(new ProjectResource($project), 'Project created successfully', 201);
    }

    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $project->load(['client', 'tasks']);

        return $this->success(new ProjectResource($project), 'Project retrieved successfully');
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validated();

        if (
            array_key_exists('status', $data)
            && is_string($data['status'])
            && $data['status'] !== $project->status
            && ! $project->canTransitionTo($data['status'])
        ) {
            return $this->error('Invalid status transition', 422);
        }

        if (($data['billing_type'] ?? $project->billing_type) === 'hourly') {
            $data['fixed_price'] = null;
        }

        if (($data['billing_type'] ?? $project->billing_type) === 'fixed') {
            $data['hourly_rate'] = null;
        }

        if (($data['status'] ?? null) === 'completed' && $project->status !== 'completed') {
            $data['completed_at'] = now();
        }

        $project->update($data);
        $project->load('client');

        return $this->success(new ProjectResource($project), 'Project updated successfully');
    }

    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return $this->success(null, 'Project deleted successfully');
    }
}
