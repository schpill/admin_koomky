<?php

namespace App\Policies;

use App\Models\SuppressedEmail;
use App\Models\User;

class SuppressedEmailPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, SuppressedEmail $suppressedEmail): bool
    {
        return $user->id === $suppressedEmail->user_id;
    }
}
