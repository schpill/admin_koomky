<?php

namespace App\Services;

use App\Models\EmailWarmupPlan;
use App\Models\User;

class WarmupGuardService
{
    public function canSend(User $user): bool
    {
        $this->resetDailyCountIfNeeded($user);

        $plan = $this->activePlan($user);
        if (! $plan instanceof EmailWarmupPlan) {
            return true;
        }

        return (int) $user->warmup_sent_today < (int) $plan->current_daily_limit;
    }

    public function incrementSentCount(User $user): void
    {
        $this->resetDailyCountIfNeeded($user);

        $user->forceFill([
            'warmup_sent_today' => (int) $user->warmup_sent_today + 1,
            'warmup_last_reset_at' => now()->toDateString(),
        ])->save();
    }

    public function resetDailyCountIfNeeded(User $user): void
    {
        $today = now()->toDateString();
        $lastResetValue = $user->warmup_last_reset_at;
        $lastReset = is_string($lastResetValue)
            ? $lastResetValue
            : null;

        if ($lastReset === $today) {
            return;
        }

        $user->forceFill([
            'warmup_sent_today' => 0,
            'warmup_last_reset_at' => $today,
        ])->save();
    }

    public function activePlan(User $user): ?EmailWarmupPlan
    {
        return EmailWarmupPlan::query()
            ->activeForUser($user)
            ->latest('started_at')
            ->first();
    }
}
