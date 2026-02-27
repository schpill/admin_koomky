<?php

namespace App\Services;

use App\Jobs\SendReminderJob;
use App\Models\Invoice;
use App\Models\InvoiceReminderSchedule;
use App\Models\ReminderStep;
use Carbon\Carbon;

class ReminderDispatchService
{
    public function dispatchDue(): int
    {
        $count = 0;

        $schedules = InvoiceReminderSchedule::query()
            ->active()
            ->with(['nextStep'])
            ->get();

        foreach ($schedules as $schedule) {
            $step = $schedule->nextStep;
            if (! $step) {
                continue;
            }

            $dueDate = Carbon::parse($schedule->started_at)->addDays((int) $step->delay_days)->startOfDay();
            if ($dueDate->gt(now()->startOfDay())) {
                continue;
            }

            SendReminderJob::dispatch($schedule->id)->onQueue('reminders');
            $count++;
        }

        return $count;
    }

    public function completeSchedule(Invoice $invoice): void
    {
        InvoiceReminderSchedule::query()
            ->where('invoice_id', $invoice->id)
            ->whereNull('completed_at')
            ->update([
                'completed_at' => now(),
                'next_reminder_step_id' => null,
                'is_paused' => false,
            ]);
    }

    public function advanceStep(InvoiceReminderSchedule $schedule): void
    {
        $schedule->loadMissing(['sequence.steps', 'nextStep']);

        $currentStep = $schedule->nextStep;
        if (! $currentStep) {
            $schedule->forceFill([
                'completed_at' => $schedule->completed_at ?? now(),
                'next_reminder_step_id' => null,
            ])->save();

            return;
        }

        $nextStep = ReminderStep::query()
            ->where('sequence_id', $currentStep->sequence_id)
            ->where('step_number', '>', $currentStep->step_number)
            ->orderBy('step_number')
            ->first();

        if ($nextStep) {
            $schedule->forceFill([
                'next_reminder_step_id' => $nextStep->id,
            ])->save();

            return;
        }

        $schedule->forceFill([
            'next_reminder_step_id' => null,
            'completed_at' => now(),
        ])->save();
    }
}
