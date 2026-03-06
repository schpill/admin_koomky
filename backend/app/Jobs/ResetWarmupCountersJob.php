<?php

namespace App\Jobs;

use App\Models\EmailWarmupPlan;
use App\Services\WarmupGuardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ResetWarmupCountersJob implements ShouldQueue
{
    use Queueable;

    public function handle(WarmupGuardService $warmupGuardService): void
    {
        EmailWarmupPlan::query()
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->each(function (EmailWarmupPlan $plan) use ($warmupGuardService): void {
                if ($plan->user === null) {
                    return;
                }

                $warmupGuardService->resetDailyCountIfNeeded($plan->user);
                $plan->advancePlan();
            });
    }
}
