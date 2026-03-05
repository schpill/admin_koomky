<?php

namespace App\Policies;

use App\Models\ImportSession;
use App\Models\User;

class ImportSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->id;
    }

    public function view(User $user, ImportSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    public function update(User $user, ImportSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    public function delete(User $user, ImportSession $session): bool
    {
        return $session->user_id === $user->id;
    }
}
