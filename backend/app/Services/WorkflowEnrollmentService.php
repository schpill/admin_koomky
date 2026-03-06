<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;

class WorkflowEnrollmentService
{
    public function __construct(
        private readonly SegmentFilterEngine $segmentFilterEngine,
        private readonly SuppressionService $suppressionService,
    ) {}

    public function enroll(Contact $contact, Workflow $workflow): ?WorkflowEnrollment
    {
        $existing = WorkflowEnrollment::query()
            ->where('workflow_id', $workflow->id)
            ->where('contact_id', $contact->id)
            ->where('status', 'active')
            ->first();

        if ($existing instanceof WorkflowEnrollment) {
            return null;
        }

        if ($workflow->entry_step_id === null) {
            return null;
        }

        $user = $workflow->user;
        if ($user instanceof User && is_string($contact->email) && $contact->email !== '') {
            if ($this->suppressionService->isSuppressed($user, $contact->email)) {
                return null;
            }
        }

        return WorkflowEnrollment::query()->create([
            'workflow_id' => $workflow->id,
            'contact_id' => $contact->id,
            'current_step_id' => $workflow->entry_step_id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
    }

    public function enrollSegment(Segment $segment, Workflow $workflow): int
    {
        $user = $workflow->user;
        if (! $user instanceof User) {
            return 0;
        }

        $contacts = $this->segmentFilterEngine
            ->apply($user, (array) $segment->filters)
            ->get();

        $count = 0;
        foreach ($contacts as $contact) {
            if ($this->enroll($contact, $workflow) instanceof WorkflowEnrollment) {
                $count++;
            }
        }

        return $count;
    }
}
