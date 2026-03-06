<?php

namespace App\Jobs;

use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use App\Services\WorkflowStepExecutor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AdvanceWorkflowEnrollmentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(WorkflowStepExecutor $executor): void
    {
        $enrollments = WorkflowEnrollment::query()
            ->with(['workflow', 'contact', 'currentStep'])
            ->dueForProcessing()
            ->get();

        foreach ($enrollments as $enrollment) {
            try {
                $step = $enrollment->currentStep;
                if (! $step instanceof WorkflowStep) {
                    $enrollment->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'last_processed_at' => now(),
                    ]);

                    continue;
                }

                $nextStepId = $executor->execute($step, $enrollment);

                if ($step->isEnd() || $nextStepId === null) {
                    $enrollment->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'last_processed_at' => now(),
                        'current_step_id' => $step->id,
                        'error_message' => null,
                    ]);

                    continue;
                }

                $enrollment->update([
                    'current_step_id' => $nextStepId,
                    'last_processed_at' => now(),
                    'error_message' => null,
                ]);
            } catch (Throwable $throwable) {
                $enrollment->update([
                    'status' => 'failed',
                    'last_processed_at' => now(),
                    'error_message' => $throwable->getMessage(),
                ]);
            }
        }
    }
}
