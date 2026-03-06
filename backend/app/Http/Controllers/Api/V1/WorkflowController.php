<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WorkflowController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workflows = Workflow::query()
            ->forUser($user)
            ->with(['steps', 'enrollments.contact'])
            ->orderBy('name')
            ->get()
            ->map(fn (Workflow $workflow): array => $this->serializeWorkflow($workflow));

        return $this->success($workflows, 'Workflows retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Workflow::class);

        /** @var User $user */
        $user = $request->user();

        $workflow = Workflow::query()->create([
            'user_id' => $user->id,
            ...$this->validateWorkflowPayload($request, false),
        ]);

        $workflow->load(['steps', 'enrollments.contact']);

        return $this->success($this->serializeWorkflow($workflow), 'Workflow created successfully', 201);
    }

    public function show(Workflow $workflow): JsonResponse
    {
        Gate::authorize('view', $workflow);

        return $this->success($this->serializeWorkflow($workflow->load(['steps', 'enrollments.contact'])), 'Workflow retrieved successfully');
    }

    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        Gate::authorize('update', $workflow);

        $workflow->update($this->validateWorkflowPayload($request, true));
        $workflow->refresh()->load(['steps', 'enrollments.contact']);

        return $this->success($this->serializeWorkflow($workflow), 'Workflow updated successfully');
    }

    public function destroy(Workflow $workflow): JsonResponse
    {
        Gate::authorize('delete', $workflow);

        $workflow->delete();

        return response()->json(null, 204);
    }

    public function activate(Workflow $workflow): JsonResponse
    {
        Gate::authorize('activate', $workflow);

        if ($workflow->entry_step_id === null) {
            return $this->error('Workflow entry step is required', 422);
        }

        $workflow->update(['status' => 'active']);
        $workflow->refresh()->load(['steps', 'enrollments.contact']);

        return $this->success($this->serializeWorkflow($workflow), 'Workflow activated successfully');
    }

    public function pause(Workflow $workflow): JsonResponse
    {
        Gate::authorize('pause', $workflow);

        $workflow->update(['status' => 'paused']);
        $workflow->refresh()->load(['steps', 'enrollments.contact']);

        return $this->success($this->serializeWorkflow($workflow), 'Workflow paused successfully');
    }

    public function storeStep(Request $request, Workflow $workflow): JsonResponse
    {
        Gate::authorize('update', $workflow);

        $step = $workflow->steps()->create($this->validateStepPayload($request));

        if ($workflow->entry_step_id === null) {
            $workflow->update(['entry_step_id' => $step->id]);
        }

        return $this->success($step, 'Workflow step created successfully', 201);
    }

    public function updateStep(Request $request, WorkflowStep $step): JsonResponse
    {
        Gate::authorize('update', $step->workflow);

        $step->update($this->validateStepPayload($request));

        return $this->success($step->fresh(), 'Workflow step updated successfully');
    }

    public function destroyStep(WorkflowStep $step): JsonResponse
    {
        Gate::authorize('update', $step->workflow);

        $workflow = $step->workflow;
        if ($workflow?->entry_step_id === $step->id) {
            $workflow->update(['entry_step_id' => null]);
        }

        WorkflowStep::query()
            ->where('workflow_id', $workflow?->id)
            ->where(function ($query) use ($step): void {
                $query->where('next_step_id', $step->id)
                    ->orWhere('else_step_id', $step->id);
            })
            ->update([
                'next_step_id' => null,
                'else_step_id' => null,
            ]);

        $step->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateWorkflowPayload(Request $request, bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger_type' => [$required, Rule::in(['email_opened', 'email_clicked', 'score_threshold', 'contact_created', 'contact_updated', 'segment_entered', 'manual'])],
            'trigger_config' => ['nullable', 'array'],
            'status' => [$partial ? 'sometimes' : 'nullable', Rule::in(['draft', 'active', 'paused', 'archived'])],
            'entry_step_id' => ['nullable', 'uuid'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStepPayload(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['send_email', 'wait', 'condition', 'update_score', 'add_tag', 'remove_tag', 'enroll_drip', 'update_field', 'end'])],
            'config' => ['sometimes', 'array'],
            'next_step_id' => ['nullable', 'uuid'],
            'else_step_id' => ['nullable', 'uuid'],
            'position_x' => ['nullable', 'numeric'],
            'position_y' => ['nullable', 'numeric'],
        ]) + ['config' => (array) $request->input('config', [])];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeWorkflow(Workflow $workflow): array
    {
        /** @var Collection<int, WorkflowStep> $steps */
        $steps = $workflow->steps;
        $enrollments = $workflow->enrollments;
        $total = $enrollments->count();
        $completed = $enrollments->where('status', 'completed')->count();

        return [
            ...$workflow->toArray(),
            'steps' => $steps->toArray(),
            'enrollments' => $enrollments->toArray(),
            'analytics' => [
                'active_enrollments' => $enrollments->where('status', 'active')->count(),
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0.0,
                'dropoff_by_step' => $steps->map(fn (WorkflowStep $step): array => [
                    'step_id' => $step->id,
                    'type' => $step->type,
                    'count' => $enrollments->where('current_step_id', $step->id)->count(),
                ])->values()->all(),
            ],
        ];
    }
}
