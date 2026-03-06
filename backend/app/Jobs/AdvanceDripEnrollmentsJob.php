<?php

namespace App\Jobs;

use App\Models\DripEnrollment;
use App\Models\DripStep;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AdvanceDripEnrollmentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $enrollments = DripEnrollment::query()
            ->with(['sequence.steps', 'contact'])
            ->active()
            ->get();

        foreach ($enrollments as $enrollment) {
            $nextPosition = $enrollment->current_step_position + 1;
            $step = $enrollment->sequence?->steps->firstWhere('position', $nextPosition);
            if (! $step instanceof DripStep) {
                continue;
            }

            $referenceTime = Carbon::parse((string) ($enrollment->last_processed_at ?? $enrollment->enrolled_at));

            if ($referenceTime->copy()->addHours((int) $step->delay_hours)->isFuture()) {
                continue;
            }

            if (! $step->evaluateCondition($enrollment)) {
                continue;
            }

            SendDripStepEmailJob::dispatch($enrollment->id, $step->id);
        }
    }
}
