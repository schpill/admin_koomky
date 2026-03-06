<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkflowEnrollmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Workflow $workflow): JsonResponse
    {
        Gate::authorize('view', $workflow);

        $perPage = min(100, max(1, (int) $request->integer('per_page', 15)));
        $enrollments = WorkflowEnrollment::query()
            ->where('workflow_id', $workflow->id)
            ->with(['contact', 'currentStep'])
            ->latest('enrolled_at')
            ->paginate($perPage);

        return $this->success($enrollments, 'Workflow enrollments retrieved successfully');
    }

    public function pause(WorkflowEnrollment $enrollment): JsonResponse
    {
        Gate::authorize('pauseEnrollment', $enrollment);

        $enrollment->update(['status' => 'paused']);

        return $this->success($enrollment->fresh(['contact', 'currentStep']), 'Workflow enrollment paused successfully');
    }

    public function resume(WorkflowEnrollment $enrollment): JsonResponse
    {
        Gate::authorize('resumeEnrollment', $enrollment);

        $enrollment->update(['status' => 'active']);

        return $this->success($enrollment->fresh(['contact', 'currentStep']), 'Workflow enrollment resumed successfully');
    }

    public function cancel(WorkflowEnrollment $enrollment): JsonResponse
    {
        Gate::authorize('cancelEnrollment', $enrollment);

        $enrollment->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return $this->success($enrollment->fresh(['contact', 'currentStep']), 'Workflow enrollment cancelled successfully');
    }
}
