<?php

namespace App\Policies;

use App\Models\CalendarConnection;
use App\Models\User;

class CalendarConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CalendarConnection $connection): bool
    {
        return $connection->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, CalendarConnection $connection): bool
    {
        return $connection->user_id === $user->id;
    }

    public function delete(User $user, CalendarConnection $connection): bool
    {
        return $connection->user_id === $user->id;
    }
}
