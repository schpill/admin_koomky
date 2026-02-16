<?php

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->id === $creditNote->user_id;
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        return $user->id === $creditNote->user_id;
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return $user->id === $creditNote->user_id;
    }
}
