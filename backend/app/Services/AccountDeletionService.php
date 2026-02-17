<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Carbon;

class AccountDeletionService
{
    public function schedule(User $user): Carbon
    {
        $purgeAt = now()->addDays(30);

        Client::query()
            ->where('user_id', $user->id)
            ->get()
            ->each
            ->delete();

        $user->forceFill([
            'deletion_scheduled_at' => $purgeAt,
        ]);

        $user->tokens()->delete();
        $user->save();
        $user->delete();

        return $purgeAt;
    }
}
