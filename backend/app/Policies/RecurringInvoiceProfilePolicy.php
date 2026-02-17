<?php

namespace App\Policies;

use App\Models\RecurringInvoiceProfile;
use App\Models\User;

class RecurringInvoiceProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RecurringInvoiceProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, RecurringInvoiceProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function delete(User $user, RecurringInvoiceProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }
}
