<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceReminderSchedule;
use App\Models\ReminderDelivery;
use App\Models\ReminderSequence;
use App\Services\ReminderDispatchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceReminderController extends Controller
{
    use ApiResponse;

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $schedule = InvoiceReminderSchedule::query()
            ->where('invoice_id', $invoice->id)
            ->with(['sequence.steps', 'nextStep', 'deliveries.step'])
            ->first();

        return $this->success($schedule, 'Invoice reminder schedule retrieved successfully');
    }

    public function attach(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $validated = $request->validate([
            'sequence_id' => ['required', 'uuid'],
        ]);

        $sequence = ReminderSequence::query()
            ->where('id', $validated['sequence_id'])
            ->where('user_id', $invoice->user_id)
            ->with('steps')
            ->firstOrFail();

        $firstStep = $sequence->steps()->orderBy('step_number')->first();

        $schedule = InvoiceReminderSchedule::query()->updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'sequence_id' => $sequence->id,
                'user_id' => $invoice->user_id,
                'started_at' => $invoice->due_date,
                'completed_at' => null,
                'is_paused' => false,
                'next_reminder_step_id' => $firstStep?->id,
            ]
        );

        return $this->success($schedule->load(['sequence.steps', 'nextStep']), 'Reminder sequence attached successfully');
    }

    public function pause(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $schedule = InvoiceReminderSchedule::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $schedule->update(['is_paused' => true]);

        return $this->success($schedule->fresh(['sequence.steps', 'nextStep']), 'Reminder schedule paused');
    }

    public function resume(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $schedule = InvoiceReminderSchedule::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $schedule->update(['is_paused' => false]);

        return $this->success($schedule->fresh(['sequence.steps', 'nextStep']), 'Reminder schedule resumed');
    }

    public function skip(Request $request, Invoice $invoice, ReminderDispatchService $dispatchService): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $schedule = InvoiceReminderSchedule::query()
            ->where('invoice_id', $invoice->id)
            ->with('nextStep')
            ->firstOrFail();

        $step = $schedule->nextStep;
        if ($step) {
            ReminderDelivery::query()->updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'reminder_step_id' => $step->id,
                ],
                [
                    'user_id' => $invoice->user_id,
                    'status' => 'skipped',
                    'sent_at' => now(),
                    'error_message' => null,
                ]
            );

            $dispatchService->advanceStep($schedule);
        }

        return $this->success($schedule->fresh(['sequence.steps', 'nextStep', 'deliveries.step']), 'Reminder step skipped');
    }

    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $schedule = InvoiceReminderSchedule::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $schedule->update([
            'completed_at' => now(),
            'next_reminder_step_id' => null,
            'is_paused' => false,
        ]);

        return response()->json(null, 204);
    }

    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        if ($request->user()?->id !== $invoice->user_id) {
            abort(403);
        }
    }
}
