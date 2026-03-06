<?php

namespace App\Policies;

use App\Models\DripSequence;
use App\Models\User;

class DripSequencePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DripSequence $dripSequence): bool
    {
        return $user->id === $dripSequence->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DripSequence $dripSequence): bool
    {
        return $user->id === $dripSequence->user_id;
    }

    public function delete(User $user, DripSequence $dripSequence): bool
    {
        return $user->id === $dripSequence->user_id;
    }
}
