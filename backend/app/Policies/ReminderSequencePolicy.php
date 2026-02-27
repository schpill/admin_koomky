<?php

namespace App\Policies;

use App\Models\ReminderSequence;
use App\Models\User;

class ReminderSequencePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReminderSequence $reminderSequence): bool
    {
        return $user->id === $reminderSequence->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ReminderSequence $reminderSequence): bool
    {
        return $user->id === $reminderSequence->user_id;
    }

    public function delete(User $user, ReminderSequence $reminderSequence): bool
    {
        return $user->id === $reminderSequence->user_id;
    }
}
