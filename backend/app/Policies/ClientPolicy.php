<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }

    public function update(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }

    public function restore(User $user, Client $client): bool
    {
        return $user->id === $client->user_id;
    }
}
