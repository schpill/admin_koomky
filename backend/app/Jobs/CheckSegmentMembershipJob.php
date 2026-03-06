<?php

namespace App\Jobs;

use App\Models\Segment;
use App\Models\Workflow;
use App\Services\WorkflowEnrollmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSegmentMembershipJob implements ShouldQueue
{
    use Queueable;

    public function handle(WorkflowEnrollmentService $workflowEnrollmentService): void
    {
        $workflows = Workflow::query()
            ->active()
            ->withTrigger('segment_entered')
            ->get();

        foreach ($workflows as $workflow) {
            $triggerConfig = $workflow->trigger_config;
            $segmentId = is_array($triggerConfig) ? ($triggerConfig['segment_id'] ?? null) : null;
            if (! is_string($segmentId)) {
                continue;
            }

            $segment = Segment::query()->find($segmentId);
            if (! $segment instanceof Segment) {
                continue;
            }

            $workflowEnrollmentService->enrollSegment($segment, $workflow);
        }
    }
}
