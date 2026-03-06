<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->id === $workflow->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->id === $workflow->user_id;
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->id === $workflow->user_id;
    }

    public function activate(User $user, Workflow $workflow): bool
    {
        return $user->id === $workflow->user_id;
    }

    public function pause(User $user, Workflow $workflow): bool
    {
        return $user->id === $workflow->user_id;
    }

    public function pauseEnrollment(User $user, WorkflowEnrollment $enrollment): bool
    {
        return $user->id === $enrollment->workflow?->user_id;
    }

    public function resumeEnrollment(User $user, WorkflowEnrollment $enrollment): bool
    {
        return $user->id === $enrollment->workflow?->user_id;
    }

    public function cancelEnrollment(User $user, WorkflowEnrollment $enrollment): bool
    {
        return $user->id === $enrollment->workflow?->user_id;
    }
}
