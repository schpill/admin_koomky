<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

final class ClientPolicy
{
    /**
     * Determine if the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        return $client->user_id === $user->id;
    }

    /**
     * Determine if the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        return $client->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        return $client->user_id === $user->id;
    }
}
